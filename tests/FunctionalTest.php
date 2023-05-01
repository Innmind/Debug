<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug;

use Innmind\Debug\Kernel as Debug;
use Innmind\Profiler\Web\Kernel as Profiler;
use Innmind\Framework\{
    Application,
    Environment,
};
use Innmind\CLI\{
    Command as CliCommand,
    Console,
    Environment\InMemory,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Filesystem\Adapter\Filesystem;
use Innmind\Server\Control\Server\Command;
use Innmind\Http\{
    Message\ServerRequest\ServerRequest,
    Message\Request\Request,
    Message\Method,
    Message\StatusCode,
    ProtocolVersion,
};
use Innmind\Router\Route;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Html\{
    Reader\Reader,
    Visitor\Elements,
    Visitor\Element,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class FunctionalTest extends TestCase
{
    use BlackBox;

    private Path $storage;

    public function setUp(): void
    {
        $this->storage = Path::of(\sys_get_temp_dir().'/innmind_debug/');
    }

    public function tearDown(): void
    {
        $storage = Filesystem::mount($this->storage);
        $storage->root()->files()->foreach(
            static fn($file) => $storage->remove($file->name()),
        );
    }

    public function testRecordHttpContext()
    {
        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->appendRoutes(static fn($routes, $_, $os) => $routes->add(
                Route::literal('GET /')->handle(static function() use ($os) {
                    $os
                        ->control()
                        ->processes()
                        ->execute(Command::foreground('cat hello'))
                        ->wait()
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );
                    $os
                        ->remote()
                        ->http()(new Request(
                            Url::of('https://github.com'),
                            Method::get,
                            ProtocolVersion::v11,
                        ))
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );

                    throw new \Exception('foo');
                }),
            ));

        try {
            $app->run(new ServerRequest(
                Url::of('/'),
                Method::get,
                ProtocolVersion::v11,
            ));
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $this->assertSame('foo', $e->getMessage());
        }

        $response = $app->run(new ServerRequest(
            Url::of('/_profiler/'),
            Method::get,
            ProtocolVersion::v11,
        ));

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::default()($response->body())
            ->flatMap(Element::body())
            ->flatMap(Element::of('main'))
            ->flatMap(Element::of('li'))
            ->flatMap(Element::of('a'))
            ->match(
                static fn($a) => $a->href(),
                static fn() => null,
            );
        $this->assertNotNull($profile);

        $response = $app->run(new ServerRequest(
            $profile,
            Method::get,
            ProtocolVersion::v11,
        ));

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::default()($response->body())
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->map(static fn($header) => Elements::of('li')($header))
            ->map(
                static fn($lis) => $lis
                    ->flatMap(Elements::of('a'))
                    ->map(static fn($a) => $a->content())
                    ->map(Str::of(...))
                    ->map(static fn($text) => $text->trim()->toString()),
            )
            ->match(
                static fn($sections) => $sections->toList(),
                static fn() => [],
            );

        $this->assertSame(
            ['Exception', 'Http', 'Environment', 'Processes', 'Remote/Http', 'App graph'],
            $sections,
        );
    }

    public function testRecordCliContext()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->command(static fn($_, $os) => new class($os) implements CliCommand {
                public function __construct(private $os)
                {
                }

                public function __invoke(Console $console): Console
                {
                    $this
                        ->os
                        ->control()
                        ->processes()
                        ->execute(Command::foreground('cat hello'))
                        ->wait()
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );
                    $this
                        ->os
                        ->remote()
                        ->http()(new Request(
                            Url::of('https://github.com'),
                            Method::get,
                            ProtocolVersion::v11,
                        ))
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );

                    throw new \Exception('foo');
                }

                public function usage(): string
                {
                    return 'hello-world';
                }
            });

        try {
            $app->run(InMemory::of(
                [],
                true,
                ['bin', 'hello-world'],
                [['PATH', \getenv('PATH')]],
                '/',
            ));
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $this->assertSame('foo', $e->getMessage());
        }

        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app());

        $response = $app->run(new ServerRequest(
            Url::of('/_profiler/'),
            Method::get,
            ProtocolVersion::v11,
        ));

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::default()($response->body())
            ->flatMap(Element::body())
            ->flatMap(Element::of('main'))
            ->flatMap(Element::of('li'))
            ->flatMap(Element::of('a'))
            ->match(
                static fn($a) => $a->href(),
                static fn() => null,
            );
        $this->assertNotNull($profile);

        $response = $app->run(new ServerRequest(
            $profile,
            Method::get,
            ProtocolVersion::v11,
        ));

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::default()($response->body())
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->map(static fn($header) => Elements::of('li')($header))
            ->map(
                static fn($lis) => $lis
                    ->flatMap(Elements::of('a'))
                    ->map(static fn($a) => $a->content())
                    ->map(Str::of(...))
                    ->map(static fn($text) => $text->trim()->toString()),
            )
            ->match(
                static fn($sections) => $sections->toList(),
                static fn() => [],
            );

        $this->assertSame(
            ['Exception', 'Environment', 'Processes', 'Remote/Http', 'App graph'],
            $sections,
        );
    }
}

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
    ServerRequest,
    Request,
    Response,
    Method,
    Response\StatusCode,
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
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    private Path $storage;

    public function setUp(): void
    {
        $this->storage = Path::of(\sys_get_temp_dir().'/innmind_debug/');
    }

    public function tearDown(): void
    {
        $storage = Filesystem::mount($this->storage);
        $storage->root()->all()->foreach(
            static fn($file) => $storage->remove($file->name()),
        );
    }

    public function testRecordHttpContext()
    {
        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp())
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
                        ->control()
                        ->processes()
                        ->execute(Command::foreground('echo hello'))
                        ->wait()
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );
                    $os
                        ->remote()
                        ->http()(Request::of(
                            Url::of('https://github.com'),
                            Method::get,
                            ProtocolVersion::v11,
                        ))
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );
                    $os
                        ->remote()
                        ->http()(Request::of(
                            Url::of('http://example.com/unknown'),
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
            $app->run(ServerRequest::of(
                Url::of('/'),
                Method::get,
                ProtocolVersion::v11,
            ));
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $this->assertSame('foo', $e->getMessage());
        }

        $response = $app->run(ServerRequest::of(
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

        $response = $app->run(ServerRequest::of(
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

    public function testRecordHttpContextWithoutOSLayer()
    {
        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
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
                        ->http()(Request::of(
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
            $app->run(ServerRequest::of(
                Url::of('/'),
                Method::get,
                ProtocolVersion::v11,
            ));
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $this->assertSame('foo', $e->getMessage());
        }

        $response = $app->run(ServerRequest::of(
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

        $response = $app->run(ServerRequest::of(
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
            ['Exception', 'Http', 'Environment', 'App graph'],
            $sections,
        );
    }

    public function testRecordHttpResponse()
    {
        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->appendRoutes(static fn($routes, $_, $os) => $routes->add(
                Route::literal('GET /')->handle(static function($request) use ($os) {
                    return Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                    );
                }),
            ));

        $response = $app->run(ServerRequest::of(
            Url::of('/'),
            Method::get,
            ProtocolVersion::v11,
        ));

        $this->assertSame(StatusCode::ok, $response->statusCode());

        $response = $app->run(ServerRequest::of(
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

        $response = $app->run(ServerRequest::of(
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
            ['Http', 'Environment', 'App graph'],
            $sections,
        );
    }

    public function testRecordCliContext()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp())
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
                        ->http()(Request::of(
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

        $response = $app->run(ServerRequest::of(
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

        $response = $app->run(ServerRequest::of(
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

    public function testRecordCliContextWithoutOSLayer()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
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
                        ->http()(Request::of(
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

        $response = $app->run(ServerRequest::of(
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

        $response = $app->run(ServerRequest::of(
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
            ['Exception', 'Environment', 'App graph'],
            $sections,
        );
    }

    public function testRecordCliReturnCode()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->command(static fn($_, $os) => new class($os) implements CliCommand {
                public function __construct(private $os)
                {
                }

                public function __invoke(Console $console): Console
                {
                    return $console->exit(1);
                }

                public function usage(): string
                {
                    return 'hello-world';
                }
            });

        $environment = $app->run(InMemory::of(
            [],
            true,
            ['bin', 'hello-world'],
            [['PATH', \getenv('PATH')]],
            '/',
        ));

        $this->assertSame(1, $environment->exitCode()->match(
            static fn($exit) => $exit->toInt(),
            static fn() => null,
        ));

        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app());

        $response = $app->run(ServerRequest::of(
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

        $response = $app->run(ServerRequest::of(
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
            ['Environment', 'App graph'],
            $sections,
        );
    }

    public function testMakeSureCorrectCommandIsRun()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->command(static fn($_, $os) => new class($os) implements CliCommand {
                public function __construct(private $os)
                {
                }

                public function __invoke(Console $console): Console
                {
                    return $console->exit(1);
                }

                public function usage(): string
                {
                    return 'foo';
                }
            })
            ->command(static fn($_, $os) => new class($os) implements CliCommand {
                public function __construct(private $os)
                {
                }

                public function __invoke(Console $console): Console
                {
                    return $console;
                }

                public function usage(): string
                {
                    return 'hello-world';
                }
            });

        $environment = $app->run(InMemory::of(
            [],
            true,
            ['bin', 'hello-world'],
            [['PATH', \getenv('PATH')]],
            '/',
        ));

        $this->assertNull($environment->exitCode()->match(
            static fn($exit) => $exit->toInt(),
            static fn() => null,
        ));
    }
}

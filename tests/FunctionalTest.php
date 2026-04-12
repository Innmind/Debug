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
    Command\Usage,
    Console,
    Environment as CliEnvironment,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Filesystem\Adapter;
use Innmind\Server\Control\Server\Command;
use Innmind\Http\{
    ServerRequest,
    Request,
    Response,
    Method,
    Response\StatusCode,
    ProtocolVersion,
};
use Innmind\DI\Service;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Html\{
    Reader,
    Visitor\Elements,
    Visitor\Element,
};
use Innmind\Immutable\{
    Str,
    Attempt,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

enum _Services implements Service
{
    case os;
}

class FunctionalTest extends TestCase
{
    private Path $storage;

    public function setUp(): void
    {
        $this->storage = Path::of(\sys_get_temp_dir().'/innmind_debug/');
    }

    public function tearDown(): void
    {
        $storage = Adapter::mount($this->storage)->unwrap();
        $_ = $storage->root()->foreach(
            static fn($file) => $storage->remove($file->name()),
        );
    }

    public function testRecordHttpContext()
    {
        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp())
            ->service(_Services::os, static fn($_, $os) => $os)
            ->route(
                static fn($pipe, $get) => $pipe
                    ->get()
                    ->endpoint('/')
                    ->handle(
                        static function() use ($get) {
                            $os = $get(_Services::os);
                            $_ = $os
                                ->control()
                                ->processes()
                                ->execute(Command::foreground('cat hello'))
                                ->flatMap(static fn($process) => $process->wait()->attempt(
                                    static fn() => new \Exception,
                                ))
                                ->match(
                                    static fn() => null,
                                    static fn() => null,
                                );
                            $_ = $os
                                ->control()
                                ->processes()
                                ->execute(Command::foreground('echo hello'))
                                ->flatMap(static fn($process) => $process->wait()->attempt(
                                    static fn() => new \Exception,
                                ))
                                ->match(
                                    static fn() => null,
                                    static fn() => null,
                                );
                            $_ = $os
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
                            $_ = $os
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

                            return Attempt::error(new \Exception('foo'));
                        },
                    ),
            );

        $this->assert()->throws(
            static fn() => $app
                ->run(ServerRequest::of(
                    Url::of('/'),
                    Method::get,
                    ProtocolVersion::v11,
                ))
                ->unwrap(),
        );

        $response = $app->run(ServerRequest::of(
            Url::of('/_profiler/'),
            Method::get,
            ProtocolVersion::v11,
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::new()($response->body())
            ->maybe()
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
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::new()($response->body())
            ->maybe()
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->toSequence()
            ->flatMap(Elements::of('li'))
            ->flatMap(Elements::of('a'))
            ->flatMap(static fn($a) => $a->normalize()->children())
            ->map(static fn($a) => $a->content())
            ->map(Str::of(...))
            ->map(static fn($text) => $text->trim()->toString())
            ->toList();

        $this->assertSame(
            ['Exception', 'Http', 'Environment', 'Processes', 'Remote/Http'],
            $sections,
        );
    }

    public function testRecordHttpContextWithoutOSLayer()
    {
        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->service(_Services::os, static fn($_, $os) => $os)
            ->route(
                static fn($pipe, $get) => $pipe
                    ->get()
                    ->endpoint('/')
                    ->handle(
                        static function() use ($get) {
                            $os = $get(_Services::os);
                            $_ = $os
                                ->control()
                                ->processes()
                                ->execute(Command::foreground('cat hello'))
                                ->maybe()
                                ->flatMap(static fn($process) => $process->wait()->maybe())
                                ->match(
                                    static fn() => null,
                                    static fn() => null,
                                );
                            $_ = $os
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

                            return Attempt::error(new \Exception('foo'));
                        },
                    ),
            );

        $error = $app
            ->run(ServerRequest::of(
                Url::of('/'),
                Method::get,
                ProtocolVersion::v11,
            ))
            ->match(
                static fn() => null,
                static fn($e) => $e,
            );

        $this->assertSame('foo', $error->getMessage());

        $response = $app->run(ServerRequest::of(
            Url::of('/_profiler/'),
            Method::get,
            ProtocolVersion::v11,
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::new()($response->body())
            ->maybe()
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
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::new()($response->body())
            ->maybe()
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->toSequence()
            ->flatMap(Elements::of('li'))
            ->flatMap(Elements::of('a'))
            ->flatMap(static fn($a) => $a->normalize()->children())
            ->map(static fn($a) => $a->content())
            ->map(Str::of(...))
            ->map(static fn($text) => $text->trim()->toString())
            ->toList();

        $this->assertSame(
            ['Exception', 'Http', 'Environment'],
            $sections,
        );
    }

    public function testRecordHttpResponse()
    {
        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->route(
                static fn($pipe, $get) => $pipe
                    ->get()
                    ->endpoint('/')
                    ->handle(static fn($request) => Attempt::result(Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                    ))),
            );

        $response = $app->run(ServerRequest::of(
            Url::of('/'),
            Method::get,
            ProtocolVersion::v11,
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());

        $response = $app->run(ServerRequest::of(
            Url::of('/_profiler/'),
            Method::get,
            ProtocolVersion::v11,
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::new()($response->body())
            ->maybe()
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
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::new()($response->body())
            ->maybe()
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->toSequence()
            ->flatMap(Elements::of('li'))
            ->flatMap(Elements::of('a'))
            ->flatMap(static fn($a) => $a->normalize()->children())
            ->map(static fn($a) => $a->content())
            ->map(Str::of(...))
            ->map(static fn($text) => $text->trim()->toString())
            ->toList();

        $this->assertSame(
            ['Http', 'Environment'],
            $sections,
        );
    }

    public function testRecordCliContext()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp())
            ->service(_Services::os, static fn($_, $os) => $os)
            ->command(static fn($get) => new class($get(_Services::os)) implements CliCommand {
                public function __construct(private $os)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    $_ = $this
                        ->os
                        ->control()
                        ->processes()
                        ->execute(Command::foreground('cat hello'))
                        ->maybe()
                        ->flatMap(static fn($process) => $process->wait()->maybe())
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );
                    $_ = $this
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

                    return Attempt::error(new \Exception('foo'));
                }

                public function usage(): Usage
                {
                    return Usage::parse('hello-world');
                }
            });

        $error = $app
            ->run(CliEnvironment::inMemory(
                [],
                true,
                ['bin', 'hello-world'],
                [['PATH', \getenv('PATH')]],
                '/',
            ))
            ->match(
                static fn() => null,
                static fn($e) => $e,
            );

        $this->assertSame('foo', $error->getMessage());

        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app());

        $response = $app->run(ServerRequest::of(
            Url::of('/_profiler/'),
            Method::get,
            ProtocolVersion::v11,
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::new()($response->body())
            ->maybe()
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
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::new()($response->body())
            ->maybe()
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->toSequence()
            ->flatMap(Elements::of('li'))
            ->flatMap(Elements::of('a'))
            ->flatMap(static fn($a) => $a->normalize()->children())
            ->map(static fn($a) => $a->content())
            ->map(Str::of(...))
            ->map(static fn($text) => $text->trim()->toString())
            ->toList();

        $this->assertSame(
            ['Exception', 'Environment', 'Processes', 'Remote/Http'],
            $sections,
        );
    }

    public function testRecordCliContextWithoutOSLayer()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->service(_Services::os, static fn($_, $os) => $os)
            ->command(static fn($get) => new class($get(_Services::os)) implements CliCommand {
                public function __construct(private $os)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    $_ = $this
                        ->os
                        ->control()
                        ->processes()
                        ->execute(Command::foreground('cat hello'))
                        ->maybe()
                        ->flatMap(static fn($process) => $process->wait()->maybe())
                        ->match(
                            static fn() => null,
                            static fn() => null,
                        );
                    $_ = $this
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

                    return Attempt::error(new \Exception('foo'));
                }

                public function usage(): Usage
                {
                    return Usage::parse('hello-world');
                }
            });

        $error = $app
            ->run(CliEnvironment::inMemory(
                [],
                true,
                ['bin', 'hello-world'],
                [['PATH', \getenv('PATH')]],
                '/',
            ))
            ->match(
                static fn() => null,
                static fn($e) => $e,
            );

        $this->assertSame('foo', $error->getMessage());

        $app = Application::http(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Debug::inApp()->operatingSystem())
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app());

        $response = $app->run(ServerRequest::of(
            Url::of('/_profiler/'),
            Method::get,
            ProtocolVersion::v11,
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::new()($response->body())
            ->maybe()
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
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::new()($response->body())
            ->maybe()
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->toSequence()
            ->flatMap(Elements::of('li'))
            ->flatMap(Elements::of('a'))
            ->flatMap(static fn($a) => $a->normalize()->children())
            ->map(static fn($a) => $a->content())
            ->map(Str::of(...))
            ->map(static fn($text) => $text->trim()->toString())
            ->toList();

        $this->assertSame(
            ['Exception', 'Environment'],
            $sections,
        );
    }

    public function testRecordCliReturnCode()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->command(static fn() => new class implements CliCommand {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(1));
                }

                public function usage(): Usage
                {
                    return Usage::parse('hello-world');
                }
            });

        $environment = $app->run(CliEnvironment::inMemory(
            [],
            true,
            ['bin', 'hello-world'],
            [['PATH', \getenv('PATH')]],
            '/',
        ))->unwrap();

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
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $profile = Reader::new()($response->body())
            ->maybe()
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
        ))->unwrap();

        $this->assertSame(StatusCode::ok, $response->statusCode());
        $sections = Reader::new()($response->body())
            ->maybe()
            ->flatMap(Element::body())
            ->flatMap(Element::of('header'))
            ->toSequence()
            ->flatMap(Elements::of('li'))
            ->flatMap(Elements::of('a'))
            ->flatMap(static fn($a) => $a->normalize()->children())
            ->map(static fn($a) => $a->content())
            ->map(Str::of(...))
            ->map(static fn($text) => $text->trim()->toString())
            ->toList();

        $this->assertSame(
            ['Environment'],
            $sections,
        );
    }

    public function testMakeSureCorrectCommandIsRun()
    {
        $app = Application::cli(Factory::build(), Environment::test([['PATH', \getenv('PATH')]]))
            ->map(Profiler::inApp($this->storage))
            ->map(Debug::inApp()->app())
            ->command(static fn() => new class implements CliCommand {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(1));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo');
                }
            })
            ->command(static fn() => new class implements CliCommand {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console);
                }

                public function usage(): Usage
                {
                    return Usage::parse('hello-world');
                }
            });

        $environment = $app->run(CliEnvironment::inMemory(
            [],
            true,
            ['bin', 'hello-world'],
            [['PATH', \getenv('PATH')]],
            '/',
        ))->unwrap();

        $this->assertNull($environment->exitCode()->match(
            static fn($exit) => $exit->toInt(),
            static fn() => null,
        ));
    }
}

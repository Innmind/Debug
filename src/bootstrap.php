<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Debug\OperatingSystem\{
    Debug as DebugOS,
    Control,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\HttpFramework\RequestHandler;
use Innmind\CLI\Command;
use Innmind\Url\UrlInterface;
use Innmind\UrlResolver\UrlResolver;
use Innmind\StackTrace\Render;
use Innmind\ObjectGraph\Visualize;
use Innmind\Filesystem\Adapter\MemoryAdapter;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
    StreamInterface,
    Stream,
};
use function Innmind\Rest\Client\bootstrap as client;

function bootstrap(
    OperatingSystem $os,
    UrlInterface $profiler,
    MapInterface $environmentVariables = null
): array {
    $environmentVariables = $environmentVariables ?? Map::of('string', 'scalar');

    $rest = client(
        $os->remote()->http(),
        new UrlResolver,
        new MemoryAdapter
    );
    $server = $rest->server((string) $profiler);

    $renderProcess = new Control\RenderProcess\Remote(
        new Control\RenderProcess\Local
    );
    $localProcesses = new Control\Processes\State(
        $renderProcess,
        new Profiler\Section\CaptureProcesses($server)
    );
    $remoteProcesses = new Control\Processes\State(
        $renderProcess,
        Profiler\Section\CaptureProcesses::remote($server)
    );

    $captureRemoteHttp = new Profiler\Section\Remote\CaptureHttp($server);

    $debugOS = new DebugOS(
        $os,
        $localProcesses,
        $remoteProcesses,
        $renderProcess,
        $captureRemoteHttp
    );

    $profiler = new Profiler\Http(
        $server,
        $os->clock(),
        $captureHttp = new Profiler\Section\CaptureHttp($server),
        $captureException = new Profiler\Section\CaptureException($server, $os->control()->processes(), new Render),
        $captureAppGraph = new Profiler\Section\CaptureAppGraph($server, $os->control()->processes(), new Visualize),
        new Profiler\Section\CaptureCallGraph($server),
        $localProcesses,
        $remoteProcesses,
        $captureRemoteHttp,
        new Profiler\Section\CaptureEnvironment(
            $server,
            $environmentVariables->reduce(
                Set::of('string'),
                static function(SetInterface $environment, $key, $value): SetInterface {
                    return $environment->add("$key=$value");
                }
            )
        )
    );

    return [
        'os' => static function() use ($debugOS): OperatingSystem {
            return $debugOS;
        },
        'http' => static function(RequestHandler $handler) use ($profiler, $captureHttp, $captureException, $captureAppGraph): RequestHandler {
            return new HttpFramework\StartProfile(
                new HttpFramework\CaptureHttp(
                    new HttpFramework\CaptureException(
                        new HttpFramework\CaptureAppGraph(
                            $handler,
                            $captureAppGraph
                        ),
                        $captureException
                    ),
                    $captureHttp
                ),
                $profiler
            );
        },
        'cli' => static function(Command ...$commands) use ($profiler, $captureException, $captureAppGraph): StreamInterface {
            return Stream::of(Command::class, ...$commands)->map(static function($command) use ($profiler, $captureException, $captureAppGraph): Command {
                return new CLI\StartProfile(
                    new CLI\CaptureException(
                        new CLI\CaptureAppGraph(
                            $command,
                            $captureAppGraph
                        ),
                        $captureException
                    ),
                    $profiler
                );
            });
        },
    ];
}

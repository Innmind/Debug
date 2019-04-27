<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Debug\OperatingSystem as OS;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\HttpFramework\{
    RequestHandler,
    Controller,
};
use Innmind\CLI\Command;
use Innmind\Url\UrlInterface;
use Innmind\UrlResolver\UrlResolver;
use Innmind\StackTrace\{
    Render,
    Link,
};
use Innmind\ObjectGraph\{
    Visualize,
    LocationRewriter,
};
use Innmind\Filesystem\Adapter\MemoryAdapter;
use Innmind\CommandBus\CommandBus as CommandBusInterface;
use Innmind\EventBus\EventBus as EventBusInterface;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
    StreamInterface,
    Stream,
};
use function Innmind\Rest\Client\bootstrap as client;

/**
 * @param  SetInterface<string>|null $disable
 */
function bootstrap(
    OperatingSystem $os,
    UrlInterface $profiler,
    MapInterface $environmentVariables = null,
    CodeEditor $codeEditor = null,
    SetInterface $disable = null
): array {
    $environmentVariables = $environmentVariables ?? Map::of('string', 'scalar');

    switch ($codeEditor) {
        case CodeEditor::sublimeText():
            $linkException = new Link\SublimeHandler;
            $locateClass = new LocationRewriter\SublimeHandler;
            break;

        default:
            $linkException = null;
            $locateClass = null;
            break;
    }

    $rest = client(
        $os->remote()->http(),
        new UrlResolver,
        new MemoryAdapter
    );
    $server = $rest->server((string) $profiler);

    $toBeHighighted = new Profiler\Section\CaptureAppGraph\ToBeHighlighted;

    $renderProcess = new OS\Debug\Control\RenderProcess\Remote(
        new OS\Debug\Control\RenderProcess\Local
    );
    $localProcesses = new OS\Debug\Control\Processes\State(
        $renderProcess,
        new Profiler\Section\CaptureProcesses($server)
    );
    $remoteProcesses = new OS\Debug\Control\Processes\State(
        $renderProcess,
        Profiler\Section\CaptureProcesses::remote($server)
    );

    $captureRemoteHttp = new Profiler\Section\Remote\CaptureHttp($server);
    $captureCallGraph = new Profiler\Section\CaptureCallGraph($server);
    $callGraph = new CallGraph($captureCallGraph, $os->clock());

    $debugOS = new OS\Debug\OperatingSystem(
        $os,
        $localProcesses,
        $remoteProcesses,
        $renderProcess,
        $captureRemoteHttp
    );
    $debugOS = new OS\CallGraph\OperatingSystem($debugOS, $callGraph);

    $captureHttp = new Profiler\Section\CaptureHttp($server);
    $captureException = new Profiler\Section\CaptureException(
        $server,
        $os->control()->processes(),
        new Render($linkException)
    );
    $captureAppGraph = new Profiler\Section\CaptureAppGraph(
        $server,
        $os->control()->processes(),
        new Visualize($locateClass),
        $toBeHighighted,
        Set::of(
            'object',
            $os,
            $os->clock(),
            $os->filesystem(),
            $os->status(),
            $os->control(),
            $os->ports(),
            $os->sockets(),
            $os->remote(),
            $os->remote()->http(),
            $os->process(),
            $os->process()->signals()
        )
    );

    $sections = Set::of(
        Profiler\Section::class,
        $captureHttp,
        $captureException,
        $captureAppGraph,
        $captureCallGraph,
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

    $profiler = new Profiler\Http(
        $server,
        $os->clock(),
        ...$sections->filter(static function(object $section) use ($disable): bool {
            return $disable === null || !$disable->contains(\get_class($section));
        })
    );

    return [
        'profiler' => static function() use ($profiler): Profiler {
            return $profiler;
        },
        'os' => static function() use ($debugOS): OperatingSystem {
            return $debugOS;
        },
        'http' => static function(RequestHandler $handler) use ($profiler, $captureHttp, $captureException, $captureAppGraph, $callGraph): RequestHandler {
            return new HttpFramework\StartProfile(
                new HttpFramework\CaptureHttp(
                    new HttpFramework\CaptureException(
                        new HttpFramework\StartCallGraph( // above app graph to not show debug stuff in the graph
                            new HttpFramework\CaptureAppGraph(
                                $handler,
                                $captureAppGraph
                            ),
                            $callGraph,
                            \get_class($handler)
                        ),
                        $captureException
                    ),
                    $captureHttp
                ),
                $profiler
            );
        },
        'cli' => static function(Command ...$commands) use ($profiler, $captureException, $captureAppGraph, $callGraph): StreamInterface {
            return Stream::of(Command::class, ...$commands)->map(static function($command) use ($profiler, $captureException, $captureAppGraph, $callGraph): Command {
                return new CLI\StartProfile(
                    new CLI\CaptureException(
                        new CLI\StartCallGraph( // above app graph to not show debug stuff in the graph
                            new CLI\CaptureAppGraph(
                                $command,
                                $captureAppGraph
                            ),
                            $callGraph,
                            \get_class($command)
                        ),
                        $captureException
                    ),
                    $profiler
                );
            });
        },
        'call_graph' => function() use ($callGraph): CallGraph {
            return $callGraph;
        },
        'controller' => static function(Controller $controller) use ($callGraph): Controller {
            return new HttpFramework\CaptureController($controller, $callGraph);
        },
        'command_bus' => static function(CommandBusInterface $bus) use ($callGraph): CommandBusInterface {
            return new CommandBus\CaptureCallGraph($bus, $callGraph);
        },
        'event_bus' => static function(EventBusInterface $bus) use ($callGraph): EventBusInterface {
            return new EventBus\CaptureCallGraph($bus, $callGraph);
        },
        'callable' => static function(callable $fn) use ($callGraph): callable {
            return new Closure\CaptureCallGraph($fn, $callGraph);
        },
    ];
}

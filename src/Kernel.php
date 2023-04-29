<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Framework\{
    Application,
    Middleware,
};

/**
 * @psalm-suppress ArgumentTypeCoercion
 */
final class Kernel implements Middleware
{
    public function __invoke(Application $app): Application
    {
        $beacon = new Recorder\Beacon;

        return $app
            ->mapOperatingSystem(static fn($os) => OperatingSystem::of(
                $os,
                $beacon,
            ))
            ->mapRequestHandler(static function($handler, $get, $_, $env) use ($beacon) {
                $recordAppGraph = new Http\RecordAppGraph(
                    new Record\Nothing,
                    $handler,
                );
                $recordException = new Http\RecordException(
                    new Record\Nothing,
                    $recordAppGraph,
                );
                $recordEnvironment = new Http\RecordEnvironment(
                    new Record\Nothing,
                    $recordException,
                    $env,
                );

                return new Http\StartProfile(
                    $get('innmind/profiler'),
                    Recorder\All::of(
                        $recordAppGraph,
                        $recordException,
                        $recordEnvironment,
                        $beacon,
                    ),
                    $recordEnvironment,
                );
            });
    }
}

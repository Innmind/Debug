<?php
declare(strict_types = 1);

namespace Innmind\Debug\Kernel;

use Innmind\Debug\{
    Http,
    Recorder,
    Record,
};
use Innmind\Framework\{
    Application,
    Middleware,
};
use Innmind\DI\Exception\ServiceNotFound;

/**
 * @internal
 * @psalm-suppress ArgumentTypeCoercion
 */
final class App implements Middleware
{
    public function __invoke(Application $app): Application
    {
        return $app
            ->mapRequestHandler(static function($handler, $get, $_, $env) {
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
                $all = [
                    $recordAppGraph,
                    $recordException,
                    $recordEnvironment,
                ];

                try {
                    $all[] = $get('innmind/debug.beacon');
                } catch (ServiceNotFound $e) {
                    // pass
                    // this means the user didn't use the OS kernel
                }

                return new Http\StartProfile(
                    $get('innmind/profiler'),
                    Recorder\All::of(...$all),
                    $recordEnvironment,
                );
            });
    }
}

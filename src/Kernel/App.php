<?php
declare(strict_types = 1);

namespace Innmind\Debug\Kernel;

use Innmind\Debug\{
    Http,
    Recorder,
    IDE,
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
    private IDE $ide;

    public function __construct(IDE $ide)
    {
        $this->ide = $ide;
    }

    public function __invoke(Application $app): Application
    {
        return $app
            ->mapRequestHandler(function($handler, $get, $os, $env) {
                $recordAppGraph = new Http\RecordAppGraph($handler, $os, $this->ide);
                $recordException = new Http\RecordException($recordAppGraph, $os, $this->ide);
                $recordEnvironment = new Http\RecordEnvironment(
                    $recordException,
                    $env,
                );
                $recordCall = new Http\RecordCall($recordEnvironment);
                $all = [
                    $recordAppGraph,
                    $recordException,
                    $recordEnvironment,
                    $recordCall,
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
                    $recordCall,
                );
            });
    }
}

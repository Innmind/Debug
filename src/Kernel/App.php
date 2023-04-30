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
use Innmind\StackTrace\FormatPath;

/**
 * @internal
 * @psalm-suppress ArgumentTypeCoercion
 */
final class App implements Middleware
{
    private IDE $ide;
    private FormatPath $formatPath;

    public function __construct(IDE $ide, FormatPath $formatPath)
    {
        $this->ide = $ide;
        $this->formatPath = $formatPath;
    }

    public function __invoke(Application $app): Application
    {
        return $app
            ->mapRequestHandler(function($handler, $get, $os, $env) {
                $path = $env->all()->filter(static fn($name) => $name === 'PATH');

                $recordAppGraph = new Http\RecordAppGraph(
                    $handler,
                    $os,
                    $path,
                    $this->ide,
                );
                $recordException = new Http\RecordException(
                    $recordAppGraph,
                    $os,
                    $path,
                    $this->ide,
                    $this->formatPath,
                );
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

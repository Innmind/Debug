<?php
declare(strict_types = 1);

namespace Innmind\Debug\Kernel;

use Innmind\Debug\{
    Http,
    Cli,
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
            ->service('innmind/debug.appGraph', fn($get, $os, $env) => new Recorder\AppGraph(
                $os,
                $env->all()->filter(static fn($name) => $name === 'PATH'),
                $this->ide,
            ))
            ->service('innmind/debug.exception', fn($get, $os, $env) => new Recorder\Exception(
                $os,
                $env->all()->filter(static fn($name) => $name === 'PATH'),
                $this->ide,
                $this->formatPath,
            ))
            ->mapCommand(static function($command, $get, $os, $env) {
                $appGraph = $get('innmind/debug.appGraph');
                $exception = $get('innmind/debug.exception');

                $recordAppGraph = new Cli\RecordAppGraph(
                    $command,
                    $appGraph,
                );
                $recordException = new Cli\RecordException(
                    $recordAppGraph,
                    $exception,
                );
                $recordEnvironment = new Cli\RecordEnvironment(
                    $recordException,
                    $env,
                );
                $all = [
                    $appGraph,
                    $exception,
                    $recordEnvironment,
                ];

                try {
                    $all[] = $get('innmind/debug.beacon');
                } catch (ServiceNotFound $e) {
                    // pass
                    // this means the user didn't use the OS kernel
                }

                return new Cli\StartProfile(
                    $get('innmind/profiler'),
                    Recorder\All::of(...$all),
                    $recordEnvironment,
                );
            })
            ->mapRequestHandler(static function($handler, $get, $os, $env) {
                $appGraph = $get('innmind/debug.appGraph');
                $exception = $get('innmind/debug.exception');

                $recordAppGraph = new Http\RecordAppGraph(
                    $handler,
                    $appGraph,
                    $get,
                );
                $recordException = new Http\RecordException(
                    $recordAppGraph,
                    $exception,
                );
                $recordEnvironment = new Http\RecordEnvironment(
                    $recordException,
                    $env,
                );
                $recordCall = new Http\RecordCall($recordEnvironment);
                $all = [
                    $appGraph,
                    $exception,
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

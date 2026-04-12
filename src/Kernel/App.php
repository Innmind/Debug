<?php
declare(strict_types = 1);

namespace Innmind\Debug\Kernel;

use Innmind\Debug\{
    Http,
    Cli,
    Recorder,
    IDE,
};
use Innmind\Profiler;
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

    #[\Override]
    public function __invoke(Application $app): Application
    {
        return $app
            ->service(Services::exception, fn($get, $os, $env) => new Recorder\Exception(
                $os,
                $env->all()->filter(static fn($name) => $name === 'PATH'),
                $this->ide,
                $this->formatPath,
            ))
            ->service(Services::httpEndProfile, static fn() => Http\EndProfile::new())
            ->service(Services::httpEnvironment, static fn($_, $__, $env) => Http\RecordEnvironment::of($env))
            ->service(Services::httpException, static fn($get) => Http\RecordException::of(
                $get(Services::exception()),
            ))
            ->service(Services::httpRecorder, static function($get) {
                try {
                    $os = [$get(Services::beacon())];
                } catch (ServiceNotFound) {
                    // this means the user didn't use the OS kernel
                    $os = [];
                }

                return Recorder\All::of(
                    $get(Services::httpEndProfile()),
                    $get(Services::httpEnvironment()),
                    $get(Services::httpException()),
                    ...$os,
                );
            })
            ->service(Services::cliRecorder, static function($get) {
                try {
                    $os = [$get(Services::beacon())];
                } catch (ServiceNotFound) {
                    // this means the user didn't use the OS kernel
                    $os = [];
                }

                return Recorder\All::of(
                    $get(Services::exception()),
                    ...$os,
                );
            })
            ->mapCommand(static fn($command, $get) => Cli\Record::of(
                $command,
                $get(Profiler\Web\Services::profiler()),
                $get(Services::cliRecorder()),
                $get(Services::exception()),
            ))
            ->mapRoutes(
                static fn($route, $get) => Http\StartProfile::of(
                    $get(Profiler\Web\Services::profiler()),
                    $get(Services::httpRecorder()),
                )
                    ->pipe($get(Services::httpEnvironment()))
                    ->pipe($route)
                    ->otherwise($get(Services::httpException()))
                    ->pipe($get(Services::httpEndProfile()))
                    ->pipe(Http\FreeProfile::of($get(Services::httpRecorder()))),
            );
    }
}

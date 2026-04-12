<?php
declare(strict_types = 1);

namespace Innmind\Debug\Kernel;

use Innmind\Debug\{
    Recorder\Beacon,
    OperatingSystem\Debug,
};
use Innmind\Framework\{
    Application,
    Middleware,
};

/**
 * @internal
 */
final class OperatingSystem implements Middleware
{
    #[\Override]
    public function __invoke(Application $app): Application
    {
        $beacon = new Beacon;

        return $app
            ->service(Services::beacon, static fn() => $beacon)
            ->mapOperatingSystem(static fn($os) => $os->map(Debug::of($beacon)));
    }
}

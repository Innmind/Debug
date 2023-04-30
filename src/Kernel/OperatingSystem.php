<?php
declare(strict_types = 1);

namespace Innmind\Debug\Kernel;

use Innmind\Debug\{
    Recorder\Beacon,
    OperatingSystem as Debug,
};
use Innmind\Framework\{
    Application,
    Middleware,
};

final class OperatingSystem implements Middleware
{
    public function __invoke(Application $app): Application
    {
        $beacon = new Beacon;

        return $app
            ->service('innmind/debug.beacon', static fn() => $beacon)
            ->mapOperatingSystem(static fn($os) => Debug::of($os, $beacon));
    }
}

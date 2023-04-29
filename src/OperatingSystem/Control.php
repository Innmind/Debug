<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\{
    Recorder\Beacon,
    OperatingSystem\Control\Processes as Debug,
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\Immutable\Either;

final class Control implements Server
{
    private Server $inner;
    private Beacon $beacon;

    private function __construct(Server $inner, Beacon $beacon)
    {
        $this->inner = $inner;
        $this->beacon = $beacon;
    }

    public static function of(Server $inner, Beacon $beacon): self
    {
        return new self($inner, $beacon);
    }

    public function processes(): Processes
    {
        return Debug::of($this->inner->processes(), $this->beacon);
    }

    public function volumes(): Volumes
    {
        return $this->inner->volumes();
    }

    public function reboot(): Either
    {
        return $this->inner->reboot();
    }

    public function shutdown(): Either
    {
        return $this->inner->shutdown();
    }
}

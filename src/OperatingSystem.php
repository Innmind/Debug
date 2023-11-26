<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Debug\{
    OperatingSystem\Control,
    OperatingSystem\Remote as RemoteDebug,
    Recorder\Beacon,
};
use Innmind\OperatingSystem\{
    OperatingSystem as OS,
    Filesystem,
    Ports,
    Sockets,
    Remote,
    CurrentProcess,
};
use Innmind\TimeContinuum\Clock;
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;

/**
 * @internal
 */
final class OperatingSystem implements OS
{
    private OS $inner;
    private Beacon $beacon;

    private function __construct(OS $inner, Beacon $beacon)
    {
        $this->inner = $inner;
        $this->beacon = $beacon;
    }

    public static function of(OS $inner, Beacon $beacon): self
    {
        return new self($inner, $beacon);
    }

    public function map(callable $map): self
    {
        return new self(
            $this->inner->map($map),
            $this->beacon,
        );
    }

    public function clock(): Clock
    {
        return $this->inner->clock();
    }

    public function filesystem(): Filesystem
    {
        return $this->inner->filesystem();
    }

    public function status(): ServerStatus
    {
        return $this->inner->status();
    }

    public function control(): ServerControl
    {
        return Control::of($this->inner->control(), $this->beacon);
    }

    public function ports(): Ports
    {
        return $this->inner->ports();
    }

    public function sockets(): Sockets
    {
        return $this->inner->sockets();
    }

    public function remote(): Remote
    {
        return RemoteDebug::of($this->inner->remote(), $this->beacon);
    }

    public function process(): CurrentProcess
    {
        return $this->inner->process();
    }
}

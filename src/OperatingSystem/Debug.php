<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\Profiler\Section\CaptureProcesses;
use Innmind\OperatingSystem\{
    OperatingSystem,
    Filesystem,
    Ports,
    Sockets,
    Remote,
    CurrentProcess,
};
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;
use Innmind\TimeContinuum\TimeContinuumInterface;

final class Debug implements OperatingSystem
{
    private $os;
    private $captureProcesses;
    private $control;

    public function __construct(
        OperatingSystem $os,
        CaptureProcesses $captureProcesses
    ) {
        $this->os = $os;
        $this->captureProcesses = $captureProcesses;
    }

    public function clock(): TimeContinuumInterface
    {
        return $this->os->clock();
    }

    public function filesystem(): Filesystem
    {
        return $this->os->filesystem();
    }

    public function status(): ServerStatus
    {
        return $this->os->status();
    }

    public function control(): ServerControl
    {
        return $this->control ?? $this->control = new Control(
            $this->os->control(),
            $this->captureProcesses
        );
    }

    public function ports(): Ports
    {
        return $this->os->ports();
    }

    public function sockets(): Sockets
    {
        return $this->os->sockets();
    }

    public function remote(): Remote
    {
        return $this->os->remote();
    }

    public function process(): CurrentProcess
    {
        return $this->os->process();
    }
}

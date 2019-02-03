<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\{
    Profiler\Section\CaptureProcesses,
    Profiler\Section\Remote\CaptureHttp,
    OperatingSystem\Control\Processes\State,
};
use Innmind\OperatingSystem\{
    OperatingSystem,
    Filesystem,
    Ports,
    Sockets,
    Remote as RemoteInterface,
    CurrentProcess,
};
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;
use Innmind\TimeContinuum\TimeContinuumInterface;

final class Debug implements OperatingSystem
{
    private $os;
    private $localProcesses;
    private $captureHttp;
    private $control;
    private $remote;

    public function __construct(
        OperatingSystem $os,
        State $localProcesses,
        CaptureHttp $captureHttp
    ) {
        $this->os = $os;
        $this->localProcesses = $localProcesses;
        $this->captureHttp = $captureHttp;
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
            $this->localProcesses
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

    public function remote(): RemoteInterface
    {
        return $this->remote ?? $this->remote = new Remote(
            $this->os->remote(),
            $this->captureHttp
        );
    }

    public function process(): CurrentProcess
    {
        return $this->os->process();
    }
}

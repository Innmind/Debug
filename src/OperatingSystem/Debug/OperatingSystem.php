<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug;

use Innmind\Debug\{
    Profiler\Section\CaptureProcesses,
    Profiler\Section\Remote\CaptureHttp,
    OperatingSystem\Debug\Control\Processes\State,
};
use Innmind\OperatingSystem\{
    OperatingSystem as OperatingSystemInterface,
    Filesystem,
    Ports,
    Sockets,
    Remote as RemoteInterface,
    CurrentProcess,
};
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;
use Innmind\TimeContinuum\TimeContinuumInterface;

/**
 * Capture operations done via the os
 */
final class OperatingSystem implements OperatingSystemInterface
{
    private OperatingSystemInterface $os;
    private State $localProcesses;
    private State $remoteProcesses;
    private Control\RenderProcess\Remote $render;
    private CaptureHttp $captureHttp;
    private ?Control $control = null;
    private ?Remote $remote = null;

    public function __construct(
        OperatingSystemInterface $os,
        State $localProcesses,
        State $remoteProcesses,
        Control\RenderProcess\Remote $render,
        CaptureHttp $captureHttp
    ) {
        $this->os = $os;
        $this->localProcesses = $localProcesses;
        $this->remoteProcesses = $remoteProcesses;
        $this->render = $render;
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
            $this->captureHttp,
            $this->render,
            $this->remoteProcesses
        );
    }

    public function process(): CurrentProcess
    {
        return $this->os->process();
    }
}

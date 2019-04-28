<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\ToBeHighlighted;

use Innmind\Debug\{
    Profiler\Section\CaptureAppGraph\ToBeHighlighted,
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
 * Highlight os objects when accessed by the app
 */
final class OperatingSystem implements OperatingSystemInterface
{
    private $os;
    private $toBeHighlighted;

    public function __construct(
        OperatingSystemInterface $os,
        ToBeHighlighted $toBeHighlighted
    ) {
        $this->os = $os;
        $this->toBeHighlighted = $toBeHighlighted;
    }

    public function clock(): TimeContinuumInterface
    {
        $clock = $this->os->clock();
        $this->toBeHighlighted->add($clock);

        return $clock;
    }

    public function filesystem(): Filesystem
    {
        $filesystem = $this->os->filesystem();
        $this->toBeHighlighted->add($filesystem);

        return $filesystem;
    }

    public function status(): ServerStatus
    {
        $status = $this->os->status();
        $this->toBeHighlighted->add($status);

        return $status;
    }

    public function control(): ServerControl
    {
        $control = $this->os->control();
        $this->toBeHighlighted->add($control);

        return $control;
    }

    public function ports(): Ports
    {
        $ports = $this->os->ports();
        $this->toBeHighlighted->add($ports);

        return $ports;
    }

    public function sockets(): Sockets
    {
        $sockets = $this->os->sockets();
        $this->toBeHighlighted->add($sockets);

        return $sockets;
    }

    public function remote(): RemoteInterface
    {
        $remote = $this->os->remote();
        $this->toBeHighlighted->add($remote);

        return new Remote($remote, $this->toBeHighlighted);
    }

    public function process(): CurrentProcess
    {
        $process = $this->os->process();
        $this->toBeHighlighted->add($process);

        return $process;
    }
}

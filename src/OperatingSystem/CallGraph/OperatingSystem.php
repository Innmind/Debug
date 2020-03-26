<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\CallGraph;
use Innmind\OperatingSystem\{
    OperatingSystem as OperatingSystemInterface,
    Filesystem,
    Ports,
    Sockets,
    Remote as RemoteInterface,
    CurrentProcess as CurrentProcessInterface,
};
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;
use Innmind\TimeContinuum\TimeContinuumInterface;

final class OperatingSystem implements OperatingSystemInterface
{
    private OperatingSystemInterface $os;
    private CallGraph $graph;
    private ?Remote $remote = null;
    private ?CurrentProcess $process = null;

    public function __construct(OperatingSystemInterface $os, CallGraph $graph)
    {
        $this->os = $os;
        $this->graph = $graph;
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
        return $this->os->control();
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
        return $this->remote ??= new Remote(
            $this->os->remote(),
            $this->graph,
        );
    }

    public function process(): CurrentProcessInterface
    {
        return $this->process ??= new CurrentProcess(
            $this->os->process(),
            $this->graph,
        );
    }
}

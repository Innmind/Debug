<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\CallGraph;
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

final class Capture implements OperatingSystem
{
    private $os;
    private $graph;
    private $remote;

    public function __construct(OperatingSystem $os, CallGraph $graph)
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
        return $this->remote ?? $this->remote = new Remote\Capture(
            $this->os->remote(),
            $this->graph
        );
    }

    public function process(): CurrentProcess
    {
        return $this->os->process();
    }
}

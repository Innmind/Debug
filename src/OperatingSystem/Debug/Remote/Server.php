<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug\Remote;

use Innmind\Debug\{
    OperatingSystem\Debug\Control\Processes,
    OperatingSystem\Debug\Control\Processes\State,
    OperatingSystem\Debug\Control\RenderProcess\Remote,
    Profiler\Section\CaptureProcesses,
};
use Innmind\Server\Control\{
    Server as ServerInterface,
    Server\Processes as ProcessesInterface,
    Server\Volumes as VolumesInterface,
};
use Innmind\Url\Url;

final class Server implements ServerInterface
{
    private ServerInterface $server;
    private Url $location;
    private Remote $render;
    private State $remoteProcesses;
    private ?Server\Processes $processes = null;

    public function __construct(
        ServerInterface $server,
        Url $location,
        Remote $render,
        State $remoteProcesses
    ) {
        $this->server = $server;
        $this->location = $location;
        $this->render = $render;
        $this->remoteProcesses = $remoteProcesses;
    }

    public function processes(): ProcessesInterface
    {
        return $this->processes ??= new Server\Processes(
            $this->server->processes(),
            $this->location,
            $this->render,
            $this->remoteProcesses
        );
    }

    public function volumes(): VolumesInterface
    {
        return $this->server->volumes();
    }

    public function reboot(): void
    {
        $this->server->reboot();
    }

    public function shutdown(): void
    {
        $this->server->shutdown();
    }
}

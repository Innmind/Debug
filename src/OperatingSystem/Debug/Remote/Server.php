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
};
use Innmind\Url\UrlInterface;

final class Server implements ServerInterface
{
    private $server;
    private $location;
    private $render;
    private $remoteProcesses;
    private $processes;

    public function __construct(
        ServerInterface $server,
        UrlInterface $location,
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
        return $this->processes ?? $this->processes = new Server\Processes(
            $this->server->processes(),
            $this->location,
            $this->render,
            $this->remoteProcesses
        );
    }
}

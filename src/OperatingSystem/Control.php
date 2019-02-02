<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\Profiler\Section\CaptureProcesses;
use Innmind\Server\Control\Server;

final class Control implements Server
{
    private $server;
    private $section;
    private $processes;

    public function __construct(Server $server, CaptureProcesses $section)
    {
        $this->server = $server;
        $this->section = $section;
    }

    public function processes(): Server\Processes
    {
        return $this->processes ?? $this->processes = new Control\Processes(
            $this->server->processes(),
            $this->section
        );
    }
}

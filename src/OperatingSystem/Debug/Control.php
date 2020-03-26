<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug;

use Innmind\Server\Control\Server;

final class Control implements Server
{
    private Server $server;
    private Control\Processes\State $state;
    private ?Control\Processes $processes = null;

    public function __construct(Server $server, Control\Processes\State $state)
    {
        $this->server = $server;
        $this->state = $state;
    }

    public function processes(): Server\Processes
    {
        return $this->processes ??= new Control\Processes(
            $this->server->processes(),
            $this->state,
        );
    }

    public function volumes(): Server\Volumes
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

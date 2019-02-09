<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug;

use Innmind\Server\Control\Server;

final class Control implements Server
{
    private $server;
    private $state;
    private $processes;

    public function __construct(Server $server, Control\Processes\State $state)
    {
        $this->server = $server;
        $this->state = $state;
    }

    public function processes(): Server\Processes
    {
        return $this->processes ?? $this->processes = new Control\Processes(
            $this->server->processes(),
            $this->state
        );
    }
}

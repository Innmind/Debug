<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem;

use Innmind\Debug\{
    OperatingSystem\Control,
    Profiler\Section\CaptureProcesses,
};
use Innmind\Server\Control\Server;
use Innmind\Rest\Client\Server as RestServer;
use PHPUnit\Framework\TestCase;

class ControlTest extends TestCase
{
    public function testInterface()
    {
        $server = new Control(
            $this->createMock(Server::class),
            new CaptureProcesses(
                $this->createMock(RestServer::class)
            )
        );

        $this->assertInstanceOf(Server::class, $server);
        $this->assertInstanceOf(Control\Processes::class, $server->processes());
        $this->assertSame($server->processes(), $server->processes());
    }
}
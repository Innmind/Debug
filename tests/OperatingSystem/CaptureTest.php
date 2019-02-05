<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem;

use Innmind\Debug\{
    OperatingSystem\Capture,
    OperatingSystem\Remote,
    Profiler\Section\CaptureCallGraph,
    CallGraph,
};
use Innmind\OperatingSystem\{
    OperatingSystem,
    Filesystem,
    Ports,
    Sockets,
    CurrentProcess,
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;
use Innmind\Rest\Client\Server;
use PHPUnit\Framework\TestCase;

class CaptureTest extends TestCase
{
    public function testInterface()
    {
        $os = new Capture(
            $this->createMock(OperatingSystem::class),
            new CallGraph(
                new CaptureCallGraph(
                    $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );

        $this->assertInstanceOf(OperatingSystem::class, $os);
        $this->assertInstanceOf(ServerControl::class, $os->control());
        $this->assertInstanceOf(Remote\Capture::class, $os->remote());
        $this->assertSame($os->remote(), $os->remote());
        $this->assertInstanceOf(TimeContinuumInterface::class, $os->clock());
        $this->assertInstanceOf(Filesystem::class, $os->filesystem());
        $this->assertInstanceOf(ServerStatus::class, $os->status());
        $this->assertInstanceOf(Ports::class, $os->ports());
        $this->assertInstanceOf(Sockets::class, $os->sockets());
        $this->assertInstanceOf(CurrentProcess::class, $os->process());
    }
}

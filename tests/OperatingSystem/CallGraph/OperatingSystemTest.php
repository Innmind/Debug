<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\{
    OperatingSystem\CallGraph\OperatingSystem,
    OperatingSystem\CallGraph\Remote,
    OperatingSystem\CallGraph\CurrentProcess,
    Profiler\Section\CaptureCallGraph,
    CallGraph,
};
use Innmind\OperatingSystem\{
    OperatingSystem as OperatingSystemInterface,
    Filesystem,
    Ports,
    Sockets,
};
use Innmind\TimeContinuum\Clock;
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;
use Innmind\Rest\Client\Server;
use PHPUnit\Framework\TestCase;

class OperatingSystemTest extends TestCase
{
    public function testInterface()
    {
        $os = new OperatingSystem(
            $this->createMock(OperatingSystemInterface::class),
            new CallGraph(
                new CaptureCallGraph(
                    $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            )
        );

        $this->assertInstanceOf(OperatingSystemInterface::class, $os);
        $this->assertInstanceOf(ServerControl::class, $os->control());
        $this->assertInstanceOf(Remote::class, $os->remote());
        $this->assertSame($os->remote(), $os->remote());
        $this->assertInstanceOf(Clock::class, $os->clock());
        $this->assertInstanceOf(Filesystem::class, $os->filesystem());
        $this->assertInstanceOf(ServerStatus::class, $os->status());
        $this->assertInstanceOf(Ports::class, $os->ports());
        $this->assertInstanceOf(Sockets::class, $os->sockets());
        $this->assertInstanceOf(CurrentProcess::class, $os->process());
        $this->assertSame($os->process(), $os->process());
    }
}

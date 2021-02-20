<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug;

use Innmind\Debug\{
    OperatingSystem\Debug\OperatingSystem,
    OperatingSystem\Debug\Control,
    OperatingSystem\Debug\Control\RenderProcess,
    OperatingSystem\Debug\Control\Processes\State,
    OperatingSystem\Debug\Remote,
    Profiler\Section\CaptureProcesses,
    Profiler\Section\Remote\CaptureHttp,
};
use Innmind\OperatingSystem\{
    OperatingSystem as OperatingSystemInterface,
    Filesystem,
    Ports,
    Sockets,
    CurrentProcess,
};
use Innmind\TimeContinuum\Clock;
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Rest\Client\Server;
use PHPUnit\Framework\TestCase;

class OperatingSystemTest extends TestCase
{
    public function testInterface()
    {
        $os = new OperatingSystem(
            $this->createMock(OperatingSystemInterface::class),
            new State(
                $this->createMock(RenderProcess::class),
                new CaptureProcesses(
                    $this->createMock(Server::class)
                )
            ),
            new State(
                $this->createMock(RenderProcess::class),
                CaptureProcesses::remote(
                    $this->createMock(Server::class)
                )
            ),
            new RenderProcess\Remote(
                $this->createMock(RenderProcess::class)
            ),
            new CaptureHttp(
                $this->createMock(Server::class)
            )
        );

        $this->assertInstanceOf(OperatingSystemInterface::class, $os);
        $this->assertInstanceOf(Control::class, $os->control());
        $this->assertInstanceOf(Remote::class, $os->remote());
        $this->assertSame($os->control(), $os->control());
        $this->assertSame($os->remote(), $os->remote());
        $this->assertInstanceOf(Clock::class, $os->clock());
        $this->assertInstanceOf(Filesystem::class, $os->filesystem());
        $this->assertInstanceOf(ServerStatus::class, $os->status());
        $this->assertInstanceOf(Ports::class, $os->ports());
        $this->assertInstanceOf(Sockets::class, $os->sockets());
        $this->assertInstanceOf(CurrentProcess::class, $os->process());
    }
}

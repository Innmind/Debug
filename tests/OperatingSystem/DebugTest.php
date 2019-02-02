<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem;

use Innmind\Debug\{
    OperatingSystem\Debug,
    OperatingSystem\Control,
    OperatingSystem\Remote,
    Profiler\Section\CaptureProcesses,
    Profiler\Section\Remote\CaptureHttp,
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
use Innmind\Rest\Client\Server;
use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
    public function testInterface()
    {
        $os = new Debug(
            $this->createMock(OperatingSystem::class),
            new CaptureProcesses(
                $this->createMock(Server::class)
            ),
            new CaptureHttp(
                $this->createMock(Server::class)
            )
        );

        $this->assertInstanceOf(OperatingSystem::class, $os);
        $this->assertInstanceOf(Control::class, $os->control());
        $this->assertInstanceOf(Remote::class, $os->remote());
        $this->assertSame($os->control(), $os->control());
        $this->assertSame($os->remote(), $os->remote());
        $this->assertInstanceOf(TimeContinuumInterface::class, $os->clock());
        $this->assertInstanceOf(Filesystem::class, $os->filesystem());
        $this->assertInstanceOf(ServerStatus::class, $os->status());
        $this->assertInstanceOf(Ports::class, $os->ports());
        $this->assertInstanceOf(Sockets::class, $os->sockets());
        $this->assertInstanceOf(CurrentProcess::class, $os->process());
    }
}

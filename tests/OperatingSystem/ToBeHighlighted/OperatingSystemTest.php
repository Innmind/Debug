<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\ToBeHighlighted;

use Innmind\Debug\{
    OperatingSystem\ToBeHighlighted\OperatingSystem,
    OperatingSystem\ToBeHighlighted\Remote,
    Profiler\Section\CaptureAppGraph\ToBeHighlighted,
};
use Innmind\OperatingSystem\{
    OperatingSystem as OperatingSystemInterface,
    Filesystem,
    Ports,
    Sockets,
    CurrentProcess,
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Server\Status\Server as ServerStatus;
use Innmind\Server\Control\Server as ServerControl;
use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
    public function testInterface()
    {
        $os = new OperatingSystem(
            $this->createMock(OperatingSystemInterface::class),
            new ToBeHighlighted
        );

        $this->assertInstanceOf(OperatingSystemInterface::class, $os);
        $this->assertInstanceOf(ServerControl::class, $os->control());
        $this->assertInstanceOf(Remote::class, $os->remote());
        $this->assertInstanceOf(TimeContinuumInterface::class, $os->clock());
        $this->assertInstanceOf(Filesystem::class, $os->filesystem());
        $this->assertInstanceOf(ServerStatus::class, $os->status());
        $this->assertInstanceOf(Ports::class, $os->ports());
        $this->assertInstanceOf(Sockets::class, $os->sockets());
        $this->assertInstanceOf(CurrentProcess::class, $os->process());
    }
}

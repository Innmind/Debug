<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Control\RenderProcess;

use Innmind\Debug\OperatingSystem\Control\{
    RenderProcess\Remote,
    RenderProcess,
};
use Innmind\Server\Control\Server\{
    Command,
    Process,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RenderProcess::class,
            new Remote(
                $this->createMock(RenderProcess::class)
            )
        );
    }

    public function testRenderLocalProcess()
    {
        $render = new Remote(
            $inner = $this->createMock(RenderProcess::class)
        );
        $command = Command::foreground('foo');
        $process = $this->createMock(Process::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($command, $process)
            ->willReturn('process');

        $this->assertSame('process', $render($command, $process));
    }

    public function testRenderRemoteProcess()
    {
        $render = new Remote(
            $inner = $this->createMock(RenderProcess::class)
        );
        $command = Command::foreground('foo');
        $process = $this->createMock(Process::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($command, $process)
            ->willReturn('process');

        $this->assertNull($render->locate(
            $command,
            Url::fromString('ssh://user:pwd@example.com:2242/foo')
        ));
        $this->assertSame(
            "ssh: user:pwd@example.com:2242\nprocess",
            $render($command, $process)
        );
    }
}

<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug\Control\RenderProcess;

use Innmind\Debug\OperatingSystem\Debug\Control\{
    RenderProcess\Local,
    RenderProcess,
};
use Innmind\Server\Control\Server\{
    Command,
    Process,
    Process\ExitCode,
    Process\Output,
};
use PHPUnit\Framework\TestCase;

class LocalTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(RenderProcess::class, new Local);
    }

    public function testRenderBackgroundProcess()
    {
        $render = new Local;
        $command = Command::background('sleep')
            ->withArgument('42000');
        $process = $this->createMock(Process::class);

        $this->assertSame("[background] sleep '42000'\n", $render($command, $process));
    }

    public function testRenderStillRunningProcess()
    {
        $render = new Local;
        $command = Command::foreground('sleep')
            ->withArgument('42000');
        $process = $this->createMock(Process::class);
        $process
            ->expects($this->any())
            ->method('isRunning')
            ->willReturn(true);

        $this->assertSame("[still-running] sleep '42000'\n", $render($command, $process));
    }

    public function testRenderFinishedProcess()
    {
        $render = new Local;
        $command = Command::foreground('sleep')
            ->withArgument('42000');
        $process = $this->createMock(Process::class);
        $process
            ->expects($this->any())
            ->method('isRunning')
            ->willReturn(false);
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(127));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('some output');

        $this->assertSame("[127] sleep '42000'\nsome output", $render($command, $process));
    }

    public function testRenderProcessWithinAWorkingDirectory()
    {
        $render = new Local;
        $command = Command::background('sleep')
            ->withArgument('42000')
            ->withWorkingDirectory('/home/some-user');
        $process = $this->createMock(Process::class);

        $this->assertSame(
            "[background] /home/some-user: sleep '42000'\n",
            $render($command, $process)
        );
    }
}

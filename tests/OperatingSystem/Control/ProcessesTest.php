<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Control;

use Innmind\Debug\{
    OperatingSystem\Control\Processes,
    OperatingSystem\Control\RenderProcess,
    Profiler\Section,
    Profiler\Section\CaptureProcesses,
    Profiler\Profile\Identity,
};
use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Process,
    Process\Pid,
    Process\ExitCode,
    Process\Output,
    Command,
    Signal,
};
use Innmind\Rest\Client\Server;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class ProcessesTest extends TestCase
{
    public function testInterface()
    {
        $processes = new Processes(
            $this->createMock(ProcessesInterface::class),
            new CaptureProcesses(
                $this->createMock(Server::class)
            ),
            $this->createMock(RenderProcess::class)
        );

        $this->assertInstanceOf(ProcessesInterface::class, $processes);
        $this->assertInstanceOf(Section::class, $processes);
    }

    public function testExecute()
    {
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            new CaptureProcesses(
                $this->createMock(Server::class)
            ),
            $this->createMock(RenderProcess::class)
        );
        $command = Command::foreground('echo');
        $inner
            ->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame($process, $processes->execute($command));
    }

    public function testKill()
    {
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            new CaptureProcesses(
                $this->createMock(Server::class)
            ),
            $this->createMock(RenderProcess::class)
        );
        $pid = new Pid(42);
        $inner
            ->expects($this->once())
            ->method('kill')
            ->with($pid, Signal::kill())
            ->will($this->returnSelf());

        $this->assertSame($processes, $processes->kill($pid, Signal::kill()));
    }

    public function testProcessesStartedBeforeProfilingStartAreNotSent()
    {
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            new CaptureProcesses(
                $server = $this->createMock(Server::class)
            ),
            $this->createMock(RenderProcess::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $processes->execute(Command::foreground('echo'));
        $processes->start(new Identity('profile-uuid'));
        $processes->finish(new Identity('profile-uuid'));
    }

    public function testSendProcesses()
    {
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            new CaptureProcesses(
                $server = $this->createMock(Server::class)
            ),
            new RenderProcess\Local
        );
        $inner
            ->expects($this->at(0))
            ->method('execute')
            ->willReturn($process1 = $this->createMock(Process::class));
        $process1
            ->expects($this->any())
            ->method('isRunning')
            ->willReturn(false);
        $process1
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process1
            ->expects($this->once())
            ->method('output')
            ->willReturn($output1 = $this->createMock(Output::class));
        $output1
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('back');
        $inner
            ->expects($this->at(1))
            ->method('execute')
            ->willReturn($process2 = $this->createMock(Process::class));
        $process2
            ->expects($this->any())
            ->method('isRunning')
            ->willReturn(true);
        $inner
            ->expects($this->at(3))
            ->method('execute')
            ->willReturn($process4 = $this->createMock(Process::class));
        $process4
            ->expects($this->any())
            ->method('isRunning')
            ->willReturn(false);
        $process4
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(127));
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('processes')->value()->equals(Set::of(
                    'string',
                    "[0] echo\nback",
                    "[still-running] sleep '42000'\n",
                    "[background] /home/some-user: sleep '42000'\n",
                    "[127] unknown\n"
                ));
            }));

        $processes->start(new Identity('profile-uuid'));
        $processes->execute(Command::foreground('echo'));
        $processes->execute(
            Command::foreground('sleep')
                ->withArgument('42000')
        );
        $processes->execute(
            Command::background('sleep')
                ->withArgument('42000')
                ->withWorkingDirectory('/home/some-user')
        );
        $processes->execute(Command::foreground('unknown'));
        $processes->finish(new Identity('profile-uuid'));
    }
}

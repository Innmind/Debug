<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug\Control;

use Innmind\Debug\{
    OperatingSystem\Debug\Control\Processes,
    OperatingSystem\Debug\Control\Processes\State,
    OperatingSystem\Debug\Control\RenderProcess,
    Profiler\Section\CaptureProcesses,
    Profiler\Profile\Identity,
};
use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Process,
    Process\Pid,
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
            new State(
                $this->createMock(RenderProcess::class),
                new CaptureProcesses(
                    $this->createMock(Server::class)
                )
            )
        );

        $this->assertInstanceOf(ProcessesInterface::class, $processes);
    }

    public function testExecute()
    {
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            new State(
                $this->createMock(RenderProcess::class),
                new CaptureProcesses(
                    $this->createMock(Server::class)
                )
            )
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
            new State(
                $this->createMock(RenderProcess::class),
                new CaptureProcesses(
                    $this->createMock(Server::class)
                )
            )
        );
        $pid = new Pid(42);
        $inner
            ->expects($this->once())
            ->method('kill')
            ->with($pid, Signal::kill())
            ->will($this->returnSelf());

        $this->assertNull($processes->kill($pid, Signal::kill()));
    }

    public function testProcessesStartedBeforeProfilingStartAreNotSent()
    {
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            $state = new State(
                $this->createMock(RenderProcess::class),
                new CaptureProcesses(
                    $server = $this->createMock(Server::class)
                )
            )
        );
        $server
            ->expects($this->never())
            ->method('create');

        $processes->execute(Command::foreground('echo'));
        $state->start(new Identity('profile-uuid'));
        $state->finish(new Identity('profile-uuid'));
    }

    public function testSendProcesses()
    {
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            $state = new State(
                $render = $this->createMock(RenderProcess::class),
                new CaptureProcesses(
                    $server = $this->createMock(Server::class)
                )
            )
        );
        $command1 = Command::foreground('foo');
        $command2 = Command::foreground('foo');
        $inner
            ->expects($this->at(0))
            ->method('execute')
            ->with($command1)
            ->willReturn($process1 = $this->createMock(Process::class));
        $inner
            ->expects($this->at(1))
            ->method('execute')
            ->with($command2)
            ->willReturn($process2 = $this->createMock(Process::class));
        $render
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($command1, $process1)
            ->willReturn('foo');
        $render
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($command2, $process2)
            ->willReturn('bar');
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('processes')->value()->equals(Set::of(
                    'string',
                    'foo',
                    'bar'
                ));
            }));

        $state->start(new Identity('profile-uuid'));
        $processes->execute($command1);
        $processes->execute($command2);
        $state->finish(new Identity('profile-uuid'));
    }
}

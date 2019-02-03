<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Remote\Server;

use Innmind\Debug\{
    OperatingSystem\Remote\Server\Processes,
    OperatingSystem\Control\Processes\State,
    OperatingSystem\Control\RenderProcess\Remote,
    OperatingSystem\Control\RenderProcess,
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
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class ProcessesTest extends TestCase
{
    public function testInterface()
    {
        $render = new Remote(
            $this->createMock(RenderProcess::class)
        );
        $processes = new Processes(
            $this->createMock(ProcessesInterface::class),
            $this->createMock(UrlInterface::class),
            $render,
            new State(
                $render,
                new CaptureProcesses(
                    $this->createMock(Server::class)
                )
            )
        );

        $this->assertInstanceOf(ProcessesInterface::class, $processes);
    }

    public function testExecute()
    {
        $render = new Remote(
            $this->createMock(RenderProcess::class)
        );
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            $this->createMock(UrlInterface::class),
            $render,
            new State(
                $render,
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
        $render = new Remote(
            $this->createMock(RenderProcess::class)
        );
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            $this->createMock(UrlInterface::class),
            $render,
            new State(
                $render,
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

        $this->assertSame($processes, $processes->kill($pid, Signal::kill()));
    }

    public function testProcessesStartedBeforeProfilingStartAreNotSent()
    {
        $render = new Remote(
            $this->createMock(RenderProcess::class)
        );
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            $this->createMock(UrlInterface::class),
            $render,
            $state = new State(
                $render,
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
        $render = $render = new Remote(
            $innerRender = $this->createMock(RenderProcess::class)
        );
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            Url::fromString('ssh://user:pwd@example.com:2242/'),
            $render,
            $state = new State(
                $render,
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
        $innerRender
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($command1, $process1)
            ->willReturn('foo');
        $innerRender
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
                    "ssh: user:pwd@example.com:2242\nfoo",
                    "ssh: user:pwd@example.com:2242\nbar"
                ));
            }));

        $state->start(new Identity('profile-uuid'));
        $processes->execute($command1);
        $processes->execute($command2);
        $state->finish(new Identity('profile-uuid'));
    }
}

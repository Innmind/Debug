<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug\Remote\Server;

use Innmind\Debug\{
    OperatingSystem\Debug\Remote\Server\Processes,
    OperatingSystem\Debug\Control\Processes\State,
    OperatingSystem\Debug\Control\RenderProcess\Remote,
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
use Innmind\Url\Url;
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
            Url::of('ssh://example.com'),
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
            Url::of('ssh://example.com'),
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
            Url::of('ssh://example.com'),
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
            ->with($pid, Signal::kill());

        $this->assertNull($processes->kill($pid, Signal::kill()));
    }

    public function testProcessesStartedBeforeProfilingStartAreNotSent()
    {
        $render = new Remote(
            $this->createMock(RenderProcess::class)
        );
        $processes = new Processes(
            $inner = $this->createMock(ProcessesInterface::class),
            Url::of('ssh://example.com'),
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
            Url::of('ssh://user:pwd@example.com:2242/'),
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([$command1], [$command2])
            ->will($this->onConsecutiveCalls(
                $process1 = $this->createMock(Process::class),
                $process2 = $this->createMock(Process::class),
            ));
        $innerRender
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$command1, $process1],
                [$command2, $process2],
            )
            ->will($this->onConsecutiveCalls('foo', 'bar'));
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

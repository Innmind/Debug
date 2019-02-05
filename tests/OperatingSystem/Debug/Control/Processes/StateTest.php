<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug\Control\Processes;

use Innmind\Debug\{
    OperatingSystem\Debug\Control\Processes\State,
    OperatingSystem\Debug\Control\RenderProcess,
    Profiler\Section\CaptureProcesses,
    Profiler\Section,
    Profiler\Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\Server\{
    Command,
    Process,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Section::class,
            new State(
                $this->createMock(RenderProcess::class),
                new CaptureProcesses(
                    $this->createMock(Server::class)
                )
            )
        );
    }

    public function testProcessesStartedBeforeProfilingStartAreNotSent()
    {
        $state = new State(
            $this->createMock(RenderProcess::class),
            new CaptureProcesses(
                $server = $this->createMock(Server::class)
            )
        );
        $server
            ->expects($this->never())
            ->method('create');

        $state->register(Command::foreground('echo'), $this->createMock(Process::class));
        $state->start(new Identity('profile-uuid'));
        $state->finish(new Identity('profile-uuid'));
    }

    public function testSendProcesses()
    {
        $state = new State(
            $render = $this->createMock(RenderProcess::class),
            new CaptureProcesses(
                $server = $this->createMock(Server::class)
            )
        );
        $command1 = Command::foreground('foo');
        $command2 = Command::foreground('foo');
        $process1 = $this->createMock(Process::class);
        $process2 = $this->createMock(Process::class);
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
        $state->register($command1, $process1);
        $state->register($command2, $process2);
        $state->finish(new Identity('profile-uuid'));
    }
}

<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\{
    OperatingSystem\CallGraph\CurrentProcess,
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\OperatingSystem\{
    CurrentProcess as CurrentProcessInterface,
    CurrentProcess\ForkSide,
    CurrentProcess\Children,
    CurrentProcess\Signals,
    Exception\ForkFailed,
};
use Innmind\Server\Status\Server\Process\Pid;
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PeriodInterface,
};
use Innmind\Json\Json;
use PHPUnit\Framework\TestCase;

class CurrentProcessTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CurrentProcessInterface::class,
            new CurrentProcess(
                $this->createMock(CurrentProcessInterface::class),
                new CallGraph(
                    new CaptureCallGraph(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(TimeContinuumInterface::class)
                )
            )
        );
    }

    public function testId()
    {
        $process = new CurrentProcess(
            $inner = $this->createMock(CurrentProcessInterface::class),
            new CallGraph(
                new CaptureCallGraph(
                    $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $inner
            ->expects($this->once())
            ->method('id')
            ->willReturn($expected = new Pid(42));

        $this->assertSame($expected, $process->id());
    }

    public function testChildren()
    {
        $process = new CurrentProcess(
            $inner = $this->createMock(CurrentProcessInterface::class),
            new CallGraph(
                new CaptureCallGraph(
                    $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $inner
            ->expects($this->once())
            ->method('children')
            ->willReturn($expected = new Children);

        $this->assertSame($expected, $process->children());
    }

    public function testCaptureHalt()
    {
        $process = new CurrentProcess(
            $inner = $this->createMock(CurrentProcessInterface::class),
            $graph = new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $period = $this->createMock(PeriodInterface::class);
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [[
                        'name' => 'halt()',
                        'value' => 0,
                        'children' => [],
                    ]],
                ]);
            }));
        $inner
            ->expects($this->once())
            ->method('halt')
            ->with($period);

        $graph->start('foo');
        $this->assertNull($process->halt($period));
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }

    public function testCaptureFork()
    {
        $process = new CurrentProcess(
            $inner = $this->createMock(CurrentProcessInterface::class),
            $graph = new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [[
                        'name' => 'fork()',
                        'value' => 0,
                        'children' => [],
                    ]],
                ]);
            }));
        $inner
            ->expects($this->once())
            ->method('fork')
            ->willReturn($side = ForkSide::of(42));

        $graph->start('foo');
        $this->assertSame($side, $process->fork());
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }

    public function testCaptureForkEvenWhenItFails()
    {
        $process = new CurrentProcess(
            $inner = $this->createMock(CurrentProcessInterface::class),
            $graph = new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [[
                        'name' => 'fork()',
                        'value' => 0,
                        'children' => [],
                    ]],
                ]);
            }));
        $inner
            ->expects($this->once())
            ->method('fork')
            ->will($this->throwException(new ForkFailed));

        try {
            $graph->start('foo');
            $process->fork();
            $this->fail('it should throw');
        } catch (ForkFailed $e) {
            $graph->end();
            $section->finish(new Identity('profile-uuid'));
        }
    }

    public function testSignals()
    {
        $process = new CurrentProcess(
            $inner = $this->createMock(CurrentProcessInterface::class),
            new CallGraph(
                new CaptureCallGraph(
                    $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $inner
            ->expects($this->once())
            ->method('signals')
            ->willReturn($expected = $this->createMock(Signals::class));

        $this->assertSame($expected, $process->signals());
    }
}

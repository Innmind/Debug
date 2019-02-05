<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\{
    OperatingSystem\CallGraph\CurrentProcess,
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\OperatingSystem\CurrentProcess as CurrentProcessInterface;
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
}

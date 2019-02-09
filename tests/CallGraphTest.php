<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug;

use Innmind\Debug\{
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\TimeContinuumInterface;
use PHPUnit\Framework\TestCase;

class CallGraphTest extends TestCase
{
    public function testNothingSentWhenEndingWithoutStarting()
    {
        $graph = new CallGraph(
            $section = new CaptureCallGraph(
                $server = $this->createMock(Server::class)
            ),
            $this->createMock(TimeContinuumInterface::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($graph->end());
        $section->finish(new Identity('profile-uuid'));
    }

    public function testEnteringDoesntStartAGraph()
    {
        $graph = new CallGraph(
            $section = new CaptureCallGraph(
                $server = $this->createMock(Server::class)
            ),
            $this->createMock(TimeContinuumInterface::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($graph->enter('foo'));
        $this->assertNull($graph->end());
        $section->finish(new Identity('profile-uuid'));
    }

    public function testLeavingDoesntStartAGraph()
    {
        $graph = new CallGraph(
            $section = new CaptureCallGraph(
                $server = $this->createMock(Server::class)
            ),
            $this->createMock(TimeContinuumInterface::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($graph->leave());
        $this->assertNull($graph->end());
        $section->finish(new Identity('profile-uuid'));
    }

    public function testGraphNotSentWhenNotEndedAtProfileFinish()
    {
        $graph = new CallGraph(
            $section = new CaptureCallGraph(
                $server = $this->createMock(Server::class)
            ),
            $this->createMock(TimeContinuumInterface::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($graph->start('foo'));
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraph()
    {
        $graph = new CallGraph(
            $section = new CaptureCallGraph(
                $server = $this->createMock(Server::class)
            ),
            $this->createMock(TimeContinuumInterface::class)
        );
        $server
            ->expects($this->once())
            ->method('create');

        $graph->start('foo');
        $graph->enter('bar');
        $graph->leave();
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }
}

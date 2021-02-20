<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section;

use Innmind\Debug\{
    Profiler\Section\CaptureCallGraph,
    Profiler\Section,
    Profiler\Profile\Identity,
    CallGraph\Node,
};
use Innmind\TimeContinuum\{
    Clock,
    Earth\PointInTime\PointInTime,
};
use Innmind\Rest\Client\Server;
use Innmind\Json\Json;
use PHPUnit\Framework\TestCase;

class CaptureCallGraphTest extends TestCase
{
    private $node;

    public function setUp(): void
    {
        $clock = $this->createMock(Clock::class);
        $clock
            ->expects($this->exactly(8))
            ->method('now')
            ->will($this->onConsecutiveCalls(
                new PointInTime('2019-01-01T12:00:00.000+0000'), // enter root
                new PointInTime('2019-01-01T12:00:00.000+0000'), // enter call 1
                new PointInTime('2019-01-01T12:00:00.000+0000'), // enter call 2
                new PointInTime('2019-01-01T12:00:00.025+0000'), // leave call 2
                new PointInTime('2019-01-01T12:00:00.050+0000'), // leave call 1
                new PointInTime('2019-01-01T12:00:00.060+0000'), // enter call 3
                new PointInTime('2019-01-01T12:00:00.090+0000'), // leave call 3
                new PointInTime('2019-01-01T12:00:00.100+0000'),
            ));
        $root = Node::root(
            $clock,
            'root'
        );

        $root->enter('call 1');
        $root->enter('call 2');
        $root->leave();
        $root->leave();
        $root->enter('call 3');
        $root->leave();
        $root->end();

        $this->node = $root;
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Section::class,
            new CaptureCallGraph(
                $this->createMock(Server::class)
            )
        );
    }

    public function testGraphCapturedProfileStartIsNotSent()
    {
        $section = new CaptureCallGraph(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($section->capture($this->node));
        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }

    public function testSectionIsNotCreatedIfNoGraphCaptured()
    {
        $section = new CaptureCallGraph(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }

    public function testCreateSection()
    {
        $section = new CaptureCallGraph(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function($resource): bool {
                return $resource->name() === 'api.section.call_graph' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('graph') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('graph')->value() === Json::encode($this->node->normalize());
            }));

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->capture($this->node));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }
}

<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\EventBus;

use Innmind\Debug\{
    EventBus\CaptureCallGraph,
    CallGraph,
    Profiler\Section\CaptureCallGraph as Section,
    Profiler\Profile\Identity,
};
use Innmind\EventBus\EventBus;
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\Clock;
use Innmind\Json\Json;
use PHPUnit\Framework\TestCase;

class CaptureCallGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EventBus::class,
            new CaptureCallGraph(
                $this->createMock(EventBus::class),
                new CallGraph(
                    new Section(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(Clock::class)
                )
            )
        );
    }

    public function testSendGraph()
    {
        $dispatch = new CaptureCallGraph(
            $inner = $this->createMock(EventBus::class),
            $graph = new CallGraph(
                $section = new Section(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            )
        );
        $event = new \stdClass;
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource) use ($inner): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [
                        [
                            'name' => 'stdClass',
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($event);

        $graph->start('foo');
        $this->assertNull($dispatch($event));
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraphWhenExceptionThrown()
    {
        $dispatch = new CaptureCallGraph(
            $inner = $this->createMock(EventBus::class),
            $graph = new CallGraph(
                $section = new Section(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            )
        );
        $event = new \stdClass;
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource) use ($inner): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [
                        [
                            'name' => 'stdClass',
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->will($this->throwException(new \Exception));

        try {
            $graph->start('foo');
            $dispatch($event);

            $this->fail('it should throw');
        } catch (\Exception $e) {
            $graph->end();
            $section->finish(new Identity('profile-uuid'));
        }
    }
}

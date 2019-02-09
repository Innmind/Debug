<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\CommandBus;

use Innmind\Debug\{
    CommandBus\CaptureCallGraph,
    CallGraph,
    Profiler\Section\CaptureCallGraph as Section,
    Profiler\Profile\Identity,
};
use Innmind\CommandBus\CommandBus;
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Json\Json;
use PHPUnit\Framework\TestCase;

class CaptureCallGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBus::class,
            new CaptureCallGraph(
                $this->createMock(CommandBus::class),
                new CallGraph(
                    new Section(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(TimeContinuumInterface::class)
                )
            )
        );
    }

    public function testSendGraph()
    {
        $handle = new CaptureCallGraph(
            $inner = $this->createMock(CommandBus::class),
            $graph = new CallGraph(
                $section = new Section(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $command = new \stdClass;
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
            ->with($command);

        $graph->start('foo');
        $this->assertNull($handle($command));
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraphWhenExceptionThrown()
    {
        $handle = new CaptureCallGraph(
            $inner = $this->createMock(CommandBus::class),
            $graph = new CallGraph(
                $section = new Section(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $command = new \stdClass;
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
            ->with($command)
            ->will($this->throwException(new \Exception));

        try {
            $graph->start('foo');
            $handle($command);

            $this->fail('it should throw');
        } catch (\Exception $e) {
            $graph->end();
            $section->finish(new Identity('profile-uuid'));
        }
    }
}

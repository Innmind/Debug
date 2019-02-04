<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Closure;

use Innmind\Debug\{
    Closure\CaptureCallGraph,
    CallGraph,
    Profiler\Section\CaptureCallGraph as Section,
    Profiler\Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Json\Json;
use PHPUnit\Framework\TestCase;

class CaptureCallGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInternalType(
            'callable',
            new CaptureCallGraph(
                function(){},
                new CallGraph(
                    new Section(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(TimeContinuumInterface::class)
                )
            )
        );
    }

    public function testSendGraphWithObjectName()
    {
        $call = new CaptureCallGraph(
            $inner = new class {
                public function __invoke(...$arguments)
                {
                    return $arguments;
                }
            },
            $graph = new CallGraph(
                $section = new Section(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource) use ($inner): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [
                        [
                            'name' => \get_class($inner),
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));

        $graph->start('foo');
        $this->assertSame(['foo', 'bar'], $call('foo', 'bar'));
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraphWithAnonymousFunction()
    {
        $call = new CaptureCallGraph(
            function(...$arguments) {
                return $arguments;
            },
            $graph = new CallGraph(
                $section = new Section(
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
                    'children' => [
                        [
                            'name' => 'Closure',
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));

        $graph->start('foo');
        $this->assertSame(['foo', 'bar'], $call('foo', 'bar'));
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraphWhenExceptionThrown()
    {
        $call = new CaptureCallGraph(
            function() {
                throw new \Exception;
            },
            $graph = new CallGraph(
                $section = new Section(
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
                    'children' => [
                        [
                            'name' => 'Closure',
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));

        try {
            $graph->start('foo');
            $call();

            $this->fail('it should throw');
        } catch (\Exception $e) {
            $graph->end();
            $section->finish(new Identity('profile-uuid'));
        }
    }
}
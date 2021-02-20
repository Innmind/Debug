<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Closure;

use Innmind\Debug\{
    Closure\CaptureCallGraph,
    CallGraph,
    Profiler\Section\CaptureCallGraph as Section,
    Profiler\Section\CaptureAppGraph\ToBeHighlighted,
    Profiler\Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\Clock;
use Innmind\Json\Json;
use PHPUnit\Framework\TestCase;

class CaptureCallGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertIsCallable(
            new CaptureCallGraph(
                static function() {},
                new CallGraph(
                    new Section(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(Clock::class)
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
                $this->createMock(Clock::class)
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
            static function(...$arguments) {
                return $arguments;
            },
            $graph = new CallGraph(
                $section = new Section(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
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
            static function() {
                throw new \Exception;
            },
            $graph = new CallGraph(
                $section = new Section(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
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

    public function testMarkObjectToBeHighlighted()
    {
        $call = new CaptureCallGraph(
            $inner = new class {
                public function __invoke(...$arguments)
                {
                    return $arguments;
                }
            },
            new CallGraph(
                new Section(
                    $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            ),
            $toBeHighlighted = new ToBeHighlighted
        );

        $this->assertFalse($toBeHighlighted->get()->contains($inner));
        $this->assertSame(['foo', 'bar'], $call('foo', 'bar'));
        $this->assertTrue($toBeHighlighted->get()->contains($inner));
    }

    public function testDoesntTryToHighlightWhenNotAnObject()
    {
        $call = new CaptureCallGraph(
            $inner = [$this, 'forward'],
            new CallGraph(
                new Section(
                    $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            ),
            $toBeHighlighted = new ToBeHighlighted
        );

        $this->assertFalse($toBeHighlighted->get()->contains(\Closure::fromCallable($inner)));
        $this->assertSame(['foo', 'bar'], $call('foo', 'bar'));
        $this->assertFalse($toBeHighlighted->get()->contains(\Closure::fromCallable($inner)));
    }

    public function forward(...$arguments)
    {
        return $arguments;
    }
}

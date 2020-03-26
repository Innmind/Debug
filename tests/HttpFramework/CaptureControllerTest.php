<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    HttpFramework\CaptureController,
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\Controller;
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\Clock;
use Innmind\Router\Route;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class CaptureControllerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Controller::class,
            new CaptureController(
                $this->createMock(Controller::class),
                new CallGraph(
                    new CaptureCallGraph(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(Clock::class)
                )
            )
        );
    }

    public function testSendGraph()
    {
        $handle = new CaptureController(
            $inner = $this->createMock(Controller::class),
            $graph = new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            )
        );
        $request = $this->createMock(ServerRequest::class);
        $route = Route::of(new Route\Name('route_name'), Str::of('GET /foo'));
        $arguments = Map::of('string', 'string');
        $response = $this->createMock(Response::class);
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource) use ($inner): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [
                        [
                            'name' => \get_class($inner).'(route_name)',
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $route, $arguments)
            ->willReturn($response);

        $graph->start('foo');
        $this->assertSame($response, $handle($request, $route, $arguments));
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraphWhenExceptionThrown()
    {
        $handle = new CaptureController(
            $inner = $this->createMock(Controller::class),
            $graph = new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            )
        );
        $request = $this->createMock(ServerRequest::class);
        $route = Route::of(new Route\Name('route_name'), Str::of('GET /foo'));
        $arguments = Map::of('string', 'string');
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource) use ($inner): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [
                        [
                            'name' => \get_class($inner).'(route_name)',
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $route, $arguments)
            ->will($this->throwException(new \Exception));

        try {
            $graph->start('foo');
            $handle($request, $route, $arguments);

            $this->fail('it should throw');
        } catch (\Exception $e) {
            $graph->end();
            $section->finish(new Identity('profile-uuid'));
        }
    }
}

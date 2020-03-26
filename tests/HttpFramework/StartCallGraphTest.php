<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    HttpFramework\StartCallGraph,
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\Clock;
use PHPUnit\Framework\TestCase;

class StartCallGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new StartCallGraph(
                $this->createMock(RequestHandler::class),
                new CallGraph(
                    new CaptureCallGraph(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(Clock::class)
                ),
                'Class fqcn'
            )
        );
    }

    public function testSendGraph()
    {
        $handle = new StartCallGraph(
            $inner = $this->createMock(RequestHandler::class),
            new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            ),
            'Class fqcn'
        );
        $server
            ->expects($this->once())
            ->method('create');
        $request = $this->createMock(ServerRequest::class);
        $response = $this->createMock(Response::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response);

        $this->assertSame($response, $handle($request));
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraphWhenExceptionThrown()
    {
        $handle = new StartCallGraph(
            $inner = $this->createMock(RequestHandler::class),
            new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            ),
            'Class fqcn'
        );
        $server
            ->expects($this->once())
            ->method('create');
        $request = $this->createMock(ServerRequest::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException(new \Exception));

        try {
            $handle($request);
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $section->finish(new Identity('profile-uuid'));
        }
    }
}

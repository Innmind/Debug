<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    HttpFramework\CaptureAppGraph,
    Profiler\Section\CaptureAppGraph as Section,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\RequestHandler;
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\Server\Processes;
use Innmind\ObjectGraph\Visualize;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use PHPUnit\Framework\TestCase;

class CaptureAppGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new CaptureAppGraph(
                $this->createMock(RequestHandler::class),
                new Section(
                    $this->createMock(Server::class),
                    $this->createMock(Processes::class),
                    new Visualize
                )
            )
        );
    }

    public function testCaptureInnerHandlerThatRepresentTheRealApp()
    {
        $handle = new CaptureAppGraph(
            $inner = $this->createMock(RequestHandler::class),
            $section = new Section(
                $server = $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Visualize
            )
        );
        $request = $this->createMock(ServerRequest::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $server
            ->expects($this->once())
            ->method('create');

        $section->start(new Identity('profile-uuid'));

        $this->assertSame($response, $handle($request));

        $section->finish(new Identity('profile-uuid'));
    }
}

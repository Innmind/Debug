<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    HttpFramework\CaptureException,
    Profiler\Section\CaptureException as Section,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\RequestHandler;
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\Server\Processes;
use Innmind\StackTrace\Render;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use PHPUnit\Framework\TestCase;

class CaptureExceptionTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new CaptureException(
                $this->createMock(RequestHandler::class),
                new Section(
                    $this->createMock(Server::class),
                    $this->createMock(Processes::class),
                    new Render
                )
            )
        );
    }

    public function testDoNothingWhenNoExceptionThrown()
    {
        $handle = new CaptureException(
            $inner = $this->createMock(RequestHandler::class),
            $section = new Section(
                $server = $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Render
            )
        );
        $request = $this->createMock(ServerRequest::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $server
            ->expects($this->never())
            ->method('create');

        $section->start(new Identity('profile-uuid'));

        $this->assertSame($response, $handle($request));
    }

    public function testCaptureThrownException()
    {
        $handle = new CaptureException(
            $inner = $this->createMock(RequestHandler::class),
            $section = new Section(
                $server = $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Render
            )
        );
        $request = $this->createMock(ServerRequest::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException(new \Exception));
        $server
            ->expects($this->once())
            ->method('create');

        $section->start(new Identity('profile-uuid'));

        $this->expectException(\Exception::class);

        $handle($request);
    }
}

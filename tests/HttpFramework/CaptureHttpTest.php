<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    HttpFramework\CaptureHttp,
    Profiler\Section\CaptureHttp as Section,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\RequestHandler;
use Innmind\Rest\Client\Server;
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
};
use PHPUnit\Framework\TestCase;

class CaptureHttpTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new CaptureHttp(
                $this->createMock(RequestHandler::class),
                new Section(
                    $this->createMock(Server::class)
                )
            )
        );
    }

    public function testInvokation()
    {
        $handle = new CaptureHttp(
            $inner = $this->createMock(RequestHandler::class),
            $section = new Section(
                $server = $this->createMock(Server::class)
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
        $server
            ->expects($this->once())
            ->method('update');

        $section->start(new Identity('profile-uuid'));

        $this->assertSame($response, $handle($request));
    }

    public function testDoesntUpdateSectionWhenDecoratedHandlerThrowsAnException()
    {
        $handle = new CaptureHttp(
            $inner = $this->createMock(RequestHandler::class),
            $section = new Section(
                $server = $this->createMock(Server::class)
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
        $server
            ->expects($this->never())
            ->method('update');

        $section->start(new Identity('profile-uuid'));

        $this->expectException(\Exception::class);

        $handle($request);
    }
}

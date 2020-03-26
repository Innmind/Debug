<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    HttpFramework\StartProfile,
    Profiler,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\{
    Message\ServerRequest\ServerRequest,
    Message\Response,
    Message\Method,
    Message\StatusCode,
    ProtocolVersion,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class StartProfileTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new StartProfile(
                $this->createMock(RequestHandler::class),
                $this->createMock(Profiler::class)
            )
        );
    }

    public function testStart()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(RequestHandler::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $request = new ServerRequest(
            Url::of('/foo/bar'),
            Method::post(),
            new ProtocolVersion(2, 0)
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('POST /foo/bar HTTP/2.0')
            ->willReturn(new Identity('some-uuid'));

        $this->assertSame($response, $handle($request));
    }

    public function testFailWhenExceptionThrown()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(RequestHandler::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $request = new ServerRequest(
            Url::of('/foo/bar'),
            Method::post(),
            new ProtocolVersion(2, 0)
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException(new \Exception));
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('POST /foo/bar HTTP/2.0')
            ->willReturn($identity = new Identity('some-uuid'));
        $profiler
            ->expects($this->once())
            ->method('fail')
            ->with($identity, '500');

        $this->expectException(\Exception::class);

        $handle($request);
    }

    public function testFailWhenResponseCodeOver400()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(RequestHandler::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $request = new ServerRequest(
            Url::of('/foo/bar'),
            Method::post(),
            new ProtocolVersion(2, 0)
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(StatusCode::of('BAD_REQUEST'));
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('POST /foo/bar HTTP/2.0')
            ->willReturn($identity = new Identity('some-uuid'));
        $profiler
            ->expects($this->once())
            ->method('fail')
            ->with($identity, '400');

        $this->assertSame($response, $handle($request));
    }

    public function testSucceedWhenResponseCodeUnder400()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(RequestHandler::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $request = new ServerRequest(
            Url::of('/foo/bar'),
            Method::post(),
            new ProtocolVersion(2, 0)
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(StatusCode::of('OK'));
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('POST /foo/bar HTTP/2.0')
            ->willReturn($identity = new Identity('some-uuid'));
        $profiler
            ->expects($this->once())
            ->method('succeed')
            ->with($identity, '200');

        $this->assertSame($response, $handle($request));
    }
}

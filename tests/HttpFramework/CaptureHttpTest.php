<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    HttpFramework\CaptureHttp,
    Profiler\Section\CaptureHttp as Section,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\RequestHandler;
use Innmind\Rest\Client\{
    Server,
    Identity as RestIdentity,
};
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    Message\Method,
    Message\StatusCode,
    ProtocolVersion,
};
use Innmind\Url\Url;
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
        $request = new ServerRequest\ServerRequest(
            Url::of('/foo/bar'),
            Method::get(),
            new ProtocolVersion(1, 0)
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = new Response\Response(
                $code = StatusCode::of('OK'),
                $code->associatedReasonPhrase(),
                new ProtocolVersion(1, 0)
            ));
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('request')->value() === "GET /foo/bar HTTP/1.0\n\n\n";
            }))
            ->willReturn($identity = $this->createMock(RestIdentity::class));
        $server
            ->expects($this->once())
            ->method('update')
            ->with(
                $identity,
                $this->callback(static function($resource): bool {
                    return $resource->properties()->get('response')->value() === "HTTP/1.0 200 OK\n\n\n";
                })
            );

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
        $request = new ServerRequest\ServerRequest(
            Url::of('http://example.com'),
            Method::get(),
            new ProtocolVersion(2, 0),
        );
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

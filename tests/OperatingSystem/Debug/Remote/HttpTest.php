<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug\Remote;

use Innmind\Debug\{
    OperatingSystem\Debug\Remote\Http,
    Profiler\Section\Remote\CaptureHttp,
    Profiler\Profile\Identity,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Response\Response,
    Message\StatusCode,
    Message\Method,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Rest\Client\Server;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $transport = new Http(
            $inner = $this->createMock(Transport::class),
            $section = new CaptureHttp(
                $server = $this->createMock(Server::class)
            )
        );
        $request = new Request(
            Url::of('http://example.com/foo'),
            Method::get(),
            new ProtocolVersion(2, 0)
        );
        $response = new Response(
            $code = StatusCode::of('OK'),
            $code->associatedReasonPhrase(),
            $request->protocolVersion()
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response);
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('request')->value() === "GET http://example.com/foo HTTP/2.0\n\n\n" &&
                    $resource->properties()->get('response')->value() === "HTTP/2.0 200 OK\n\n\n";
            }));

        $this->assertInstanceOf(Transport::class, $transport);
        $this->assertSame($response, $transport($request));
        $section->finish(new Identity('profile-uuid'));
    }
}

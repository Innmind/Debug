<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem;

use Innmind\Debug\{
    OperatingSystem\Remote,
    OperatingSystem\Remote\Http,
    Profiler\Section,
    Profiler\Section\Remote\CaptureHttp,
    Profiler\Profile\Identity,
};
use Innmind\OperatingSystem\Remote as RemoteInterface;
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\Server as ServerControl;
use Innmind\Url\{
    UrlInterface,
    Url,
    AuthorityInterface,
};
use Innmind\Socket\{
    Client,
    Internet\Transport,
};
use Innmind\HttpTransport\Transport as HttpTransport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Response\Response,
    Message\StatusCode\StatusCode,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
};
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testInterface()
    {
        $remote = new Remote(
            $this->createMock(RemoteInterface::class),
            new CaptureHttp(
                $this->createMock(Server::class)
            )
        );

        $this->assertInstanceOf(RemoteInterface::class, $remote);
        $this->assertInstanceOf(Section::class, $remote);
        $this->assertInstanceOf(
            ServerControl::class,
            $remote->ssh($this->createMock(UrlInterface::class))
        );
        $this->assertInstanceOf(
            Client::class,
            $remote->socket(Transport::tcp(), $this->createMock(AuthorityInterface::class))
        );
        $this->assertInstanceOf(Http::class, $remote->http());
        $this->assertSame($remote->http(), $remote->http());
    }

    public function testSendHttpCalls()
    {
        $remote = new Remote(
            $inner = $this->createMock(RemoteInterface::class),
            new CaptureHttp(
                $server = $this->createMock(Server::class)
            )
        );
        $inner
            ->expects($this->once())
            ->method('http')
            ->willReturn($http = $this->createMock(HttpTransport::class));
        $request = new Request(
            Url::fromString('http://example.com/foo'),
            Method::get(),
            new ProtocolVersion(2, 0)
        );
        $response = new Response(
            $code = StatusCode::of('OK'),
            $code->associatedReasonPhrase(),
            $request->protocolVersion()
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response);
        $server
            ->expects($this->once())
            ->method('create');

        $this->assertNull($remote->start(new Identity('profile-uuid')));
        $remote->http()($request);
        $this->assertNull($remote->finish(new Identity('profile-uuid')));
    }
}

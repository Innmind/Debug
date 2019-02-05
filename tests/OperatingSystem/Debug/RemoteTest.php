<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug;

use Innmind\Debug\{
    OperatingSystem\Debug\Remote,
    OperatingSystem\Debug\Remote\Http,
    OperatingSystem\Debug\Control,
    Profiler\Section\Remote\CaptureHttp,
    Profiler\Section\CaptureProcesses,
    Profiler\Profile\Identity,
};
use Innmind\OperatingSystem\Remote as RemoteInterface;
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\{
    Server as ServerControl,
    Server\Command,
};
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
        $render = new Control\RenderProcess\Remote(
            new Control\RenderProcess\Local
        );
        $remote = new Remote(
            $this->createMock(RemoteInterface::class),
            new CaptureHttp(
                $this->createMock(Server::class)
            ),
            $render,
            new Control\Processes\State(
                $render,
                CaptureProcesses::remote(
                    $this->createMock(Server::class)
                )
            )
        );

        $this->assertInstanceOf(RemoteInterface::class, $remote);
        $this->assertInstanceOf(
            Remote\Server::class,
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
        $render = new Control\RenderProcess\Remote(
            new Control\RenderProcess\Local
        );
        $remote = new Remote(
            $inner = $this->createMock(RemoteInterface::class),
            $section = new CaptureHttp(
                $server = $this->createMock(Server::class)
            ),
            $render,
            new Control\Processes\State(
                $render,
                CaptureProcesses::remote(
                    $this->createMock(Server::class)
                )
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

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $remote->http()($request);
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }

    public function testSendProcesses()
    {
        $render = new Control\RenderProcess\Remote(
            new Control\RenderProcess\Local
        );
        $remote = new Remote(
            $inner = $this->createMock(RemoteInterface::class),
            new CaptureHttp(
                $server = $this->createMock(Server::class)
            ),
            $render,
            $state = new Control\Processes\State(
                $render,
                CaptureProcesses::remote(
                    $server
                )
            )
        );
        $location = Url::fromString('ssh://example.com');
        $command = Command::background('echo');
        $inner
            ->expects($this->once())
            ->method('ssh')
            ->willReturn($ssh = $this->createMock(ServerControl::class));
        $ssh
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(ServerControl\Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($command);
        $server
            ->expects($this->once())
            ->method('create');

        $state->start(new Identity('profile-uuid'));
        $remote->ssh($location)->processes()->execute($command);
        $state->finish(new Identity('profile-uuid'));
    }
}

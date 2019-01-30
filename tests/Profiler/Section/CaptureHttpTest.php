<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section;

use Innmind\Debug\Profiler\{
    Section\CaptureHttp,
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\{
    Server,
    Identity as RestIdentity,
};
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    Message\Method\Method,
    Message\StatusCode\StatusCode,
    ProtocolVersion\ProtocolVersion,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class CaptureHttpTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Section::class,
            new CaptureHttp(
                $this->createMock(Server::class)
            )
        );
    }

    public function testDoesntCreateSectionWhenProfilingNotStarted()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($section->received(new ServerRequest\Stringable(
            $this->createMock(ServerRequest::class)
        )));
    }

    public function testCreateSectionWhenRequestReceived()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.section.http' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('request') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('request')->value() === "GET /foo/bar HTTP/1.0\n\n\n";
            }));

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->received(new ServerRequest\Stringable(
            new ServerRequest\ServerRequest(
                Url::fromString('/foo/bar'),
                Method::get(),
                new ProtocolVersion(1, 0)
            )
        )));
    }

    public function testDoesntUpdateSectionWhenProfilingNotStarted()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('update');

        $this->assertNull($section->respondedWith(new Response\Stringable(
            $this->createMock(Response::class)
        )));
    }

    public function testDoesntUpdateSectionWhenProfilingHasFinished()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('update');

        $section->start($identity = new Identity('profile-uuid'));
        $this->assertNull($section->finish($identity));
        $this->assertNull($section->respondedWith(new Response\Stringable(
            $this->createMock(Response::class)
        )));
    }

    public function testDoesntUpdateSectionWhenNoRequestReceived()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('update');

        $section->start(new Identity('profile-uuid'));
        $this->assertNull($section->respondedWith(new Response\Stringable(
            $this->createMock(Response::class)
        )));
    }

    public function testUpdateSectionWhenResponseAboutToBeSent()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->willReturn($resource = $this->createMock(RestIdentity::class));
        $server
            ->expects($this->once())
            ->method('update')
            ->with(
                $resource,
                $this->callback(static function($resource): bool {
                    return $resource->name() === 'api.section.http' &&
                        $resource->properties()->contains('response') &&
                        $resource->properties()->get('response')->value() === "HTTP/1.0 200 OK\n\n\n";
                })
            );

        $section->start(new Identity('profile-uuid'));
        $section->received(new ServerRequest\Stringable(
            new ServerRequest\ServerRequest(
                Url::fromString('/foo/bar'),
                Method::get(),
                new ProtocolVersion(1, 0)
            )
        ));
        $this->assertNull($section->respondedWith(new Response\Stringable(
            new Response\Response(
                $code = StatusCode::of('OK'),
                $code->associatedReasonPhrase(),
                new ProtocolVersion(1, 0)
            )
        )));
    }
}

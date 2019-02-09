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

        $this->assertNull($section->received('request'));
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
                    $resource->properties()->get('request')->value() === 'http request';
            }));

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->received('http request'));
    }

    public function testDoesntUpdateSectionWhenProfilingNotStarted()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('update');

        $this->assertNull($section->respondedWith('response'));
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
        $this->assertNull($section->respondedWith('response'));
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
        $this->assertNull($section->respondedWith('response'));
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
                        $resource->properties()->get('response')->value() === 'http response';
                })
            );

        $section->start(new Identity('profile-uuid'));
        $section->received('request');
        $this->assertNull($section->respondedWith('http response'));
    }
}

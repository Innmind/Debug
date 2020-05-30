<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section\Remote;

use Innmind\Debug\Profiler\{
    Section\Remote\CaptureHttp,
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

    public function testCapturingDoesntCreateSection()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($section->capture('request', 'response'));
    }

    public function testCapturedPairsSentOnFinish()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.section.remote.http' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('request') &&
                    $resource->properties()->contains('response') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('request')->value() === 'request1' &&
                    $resource->properties()->get('response')->value() === 'response1';
            }))
            ->willReturn($identity = $this->createMock(RestIdentity::class));
        $server
            ->expects($this->once())
            ->method('update')
            ->with(
                $identity,
                $this->callback(static function($resource): bool {
                    return $resource->name() === 'api.section.remote.http' &&
                        $resource->properties()->contains('profile') &&
                        $resource->properties()->contains('request') &&
                        $resource->properties()->contains('response') &&
                        $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                        $resource->properties()->get('request')->value() === 'request2' &&
                        $resource->properties()->get('response')->value() === 'response2';
                })
            );

        $section->start($identity = new Identity('profile-uuid'));

        $section->capture('request1', 'response1');
        $section->capture('request2', 'response2');

        $this->assertNull($section->finish($identity));
    }

    public function testPairsAreResettedWhenStartingANewProfile()
    {
        $section = new CaptureHttp(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('create');

        $section->start(new Identity('profile-uuid1'));
        $section->capture('request1', 'response1');
        $section->finish(new Identity('profile-uuid1'));
        $section->start(new Identity('profile-uuid2'));
        $section->finish(new Identity('profile-uuid2'));
    }
}

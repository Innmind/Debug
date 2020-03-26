<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler;

use Innmind\Debug\{
    Profiler\Http,
    Profiler,
    Profiler\Profile\Identity,
    Profiler\Section,
};
use Innmind\Rest\Client\{
    Server,
    Identity\Identity as RestIdentity,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\Format\ISO8601,
};
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Profiler::class,
            new Http(
                $this->createMock(Server::class),
                $this->createMock(Clock::class)
            )
        );
    }

    public function testStart()
    {
        $profiler = new Http(
            $server = $this->createMock(Server::class),
            $clock = $this->createMock(Clock::class),
            $section = $this->createMock(Section::class)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.profile' &&
                    $resource->properties()->contains('name') &&
                    $resource->properties()->contains('started_at') &&
                    $resource->properties()->get('name')->value() === 'foo' &&
                    $resource->properties()->get('started_at')->value() === 'bar';
            }))
            ->willReturn(new RestIdentity('some-uuid'));
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn($now = $this->createMock(PointInTime::class));
        $now
            ->expects($this->once())
            ->method('format')
            ->with(new ISO8601)
            ->willReturn('bar');
        $section
            ->expects($this->once())
            ->method('start')
            ->with(new Identity('some-uuid'));

        $identity = $profiler->start('foo');

        $this->assertInstanceOf(Identity::class, $identity);
        $this->assertSame('some-uuid', $identity->toString());
    }

    public function testFail()
    {
        $profiler = new Http(
            $server = $this->createMock(Server::class),
            $clock = $this->createMock(Clock::class),
            $section = $this->createMock(Section::class)
        );
        $server
            ->expects($this->once())
            ->method('update')
            ->with(
                new RestIdentity('some-uuid'),
                $this->callback(static function($resource): bool {
                    return $resource->name() === 'api.profile' &&
                        $resource->properties()->contains('success') &&
                        $resource->properties()->contains('exit') &&
                        $resource->properties()->get('success')->value() === false &&
                        $resource->properties()->get('exit')->value() === 'bar';
                })
            );
        $clock
            ->expects($this->never())
            ->method('now');
        $section
            ->expects($this->once())
            ->method('finish')
            ->with($identity = new Identity('some-uuid'));

        $this->assertNull($profiler->fail($identity, 'bar'));
    }

    public function testSucceed()
    {
        $profiler = new Http(
            $server = $this->createMock(Server::class),
            $clock = $this->createMock(Clock::class),
            $section = $this->createMock(Section::class)
        );
        $server
            ->expects($this->once())
            ->method('update')
            ->with(
                new RestIdentity('some-uuid'),
                $this->callback(static function($resource): bool {
                    return $resource->name() === 'api.profile' &&
                        $resource->properties()->contains('success') &&
                        $resource->properties()->contains('exit') &&
                        $resource->properties()->get('success')->value() === true &&
                        $resource->properties()->get('exit')->value() === 'bar';
                })
            );
        $clock
            ->expects($this->never())
            ->method('now');
        $section
            ->expects($this->once())
            ->method('finish')
            ->with($identity = new Identity('some-uuid'));

        $this->assertNull($profiler->succeed($identity, 'bar'));
    }
}

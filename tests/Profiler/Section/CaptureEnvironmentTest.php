<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section;

use Innmind\Debug\Profiler\{
    Section\CaptureEnvironment,
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class CaptureEnvironmentTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Section::class,
            new CaptureEnvironment(
                $this->createMock(Server::class),
                Set::of('string')
            )
        );
    }

    public function testStartDoNothing()
    {
        $section = new CaptureEnvironment(
            $server = $this->createMock(Server::class),
            Set::of('string')
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($section->start(new Identity('profile-uuid')));
    }

    public function testFinishCallIsEnoughToSendEnvironment()
    {
        $section = new CaptureEnvironment(
            $server = $this->createMock(Server::class),
            $environment = Set::of('string', 'FOO=42')
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource) use ($environment): bool {
                return $resource->name() === 'api.section.environment' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('pairs') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('pairs')->value() === $environment;
            }));

        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }

    public function testDoesntSendEmptyEnvironment()
    {
        $section = new CaptureEnvironment(
            $server = $this->createMock(Server::class),
            Set::of('string')
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }
}

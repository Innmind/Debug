<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section;

use Innmind\Debug\Profiler\{
    Section\CaptureProcesses,
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class CaptureProcessesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Section::class,
            new CaptureProcesses(
                $this->createMock(Server::class)
            )
        );
    }

    public function testStartDoNothing()
    {
        $section = new CaptureProcesses(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->never())
            ->method('create');

        $this->assertNull($section->start(new Identity('profile-uuid')));
    }

    public function testFinishCallIsEnoughToSendProcesses()
    {
        $section = new CaptureProcesses(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.section.processes' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('processes') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('processes')->value()->equals(Set::of('string', 'some-command'));
            }));

        $this->assertNull($section->capture('some-command'));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }

    public function testCaptureRemoteProcesses()
    {
        $section = CaptureProcesses::remote(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.section.remote.processes' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('processes') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('processes')->value()->equals(Set::of('string', 'some-command'));
            }));

        $this->assertInstanceOf(CaptureProcesses::class, $section);
        $this->assertNull($section->capture('some-command'));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }
}

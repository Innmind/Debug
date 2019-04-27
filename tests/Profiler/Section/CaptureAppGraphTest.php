<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section;

use Innmind\Debug\Profiler\{
    Section\CaptureAppGraph,
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\Output,
};
use Innmind\ObjectGraph\Visualize;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class CaptureAppGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Section::class,
            new CaptureAppGraph(
                $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Visualize
            )
        );
    }

    public function testDoesntCreateSectionWhenProfilerNotStarted()
    {
        $section = new CaptureAppGraph(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Visualize
        );
        $server
            ->expects($this->never())
            ->method('create');
        $processes
            ->expects($this->never())
            ->method('execute');

        $this->assertNull($section->capture(new \stdClass));
    }

    public function testDoesntCreateSectionWhenProfilerStartedButNotFinished()
    {
        $section = new CaptureAppGraph(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Visualize
        );
        $server
            ->expects($this->never())
            ->method('create');
        $processes
            ->expects($this->never())
            ->method('execute');

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->capture(new \stdClass));
    }

    public function testDoesntCreateSectionWhenProfilerHasFinished()
    {
        $section = new CaptureAppGraph(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Visualize
        );
        $server
            ->expects($this->never())
            ->method('create');
        $processes
            ->expects($this->never())
            ->method('execute');

        $section->start($identity = new Identity('profile-uuid'));
        $this->assertNull($section->finish($identity));
        $this->assertNull($section->capture(new \stdClass));
    }

    public function testDoesntCreateSectionWhenCapturedBeforeStarted()
    {
        $section = new CaptureAppGraph(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Visualize
        );
        $server
            ->expects($this->never())
            ->method('create');
        $processes
            ->expects($this->never())
            ->method('execute');

        $this->assertNull($section->capture(new \stdClass));
        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }

    public function testCreateSectionWhenProfilerStarted()
    {
        $section = new CaptureAppGraph(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Visualize
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.section.app_graph' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('graph') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('graph')->value() === '<graph-output/>';
            }));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "dot '-Tsvg'" &&
                    !empty((string) $command->input());
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('<graph-output/>');

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->capture(new \stdClass));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }

    public function testDependenciesAreRemovedFromGraph()
    {
        $object = new class {
            public $a;
        };
        $dependency = new class {
            public $b;
        };
        $subDependency = new class {
            public $c;
        };
        $object->a = $dependency;
        $dependency->b = $subDependency;

        $section = new CaptureAppGraph(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Visualize,
            Set::of('object', $dependency)
        );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.section.app_graph' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('graph') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('graph')->value() === '<graph-output/>';
            }));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command) use ($subDependency): bool {
                return (string) $command === "dot '-Tsvg'" &&
                    !empty((string) $command->input()) &&
                    \substr_count((string) $command->input(), \spl_object_hash($subDependency)) === 0;
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('<graph-output/>');

        $this->assertNull($section->start(new Identity('profile-uuid')));
        $this->assertNull($section->capture($object));
        $this->assertNull($section->finish(new Identity('profile-uuid')));
    }
}

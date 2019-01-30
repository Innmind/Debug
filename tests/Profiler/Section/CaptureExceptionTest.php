<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section;

use Innmind\Debug\Profiler\{
    Section\CaptureException,
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\Output,
};
use Innmind\StackTrace\Render;
use PHPUnit\Framework\TestCase;

class CaptureExceptionTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Section::class,
            new CaptureException(
                $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Render
            )
        );
    }

    public function testDoesntCreateSectionWhenProfilerNotStarted()
    {
        $section = new CaptureException(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Render
        );
        $server
            ->expects($this->never())
            ->method('create');
        $processes
            ->expects($this->never())
            ->method('execute');

        $this->assertNull($section->capture(new \Exception));
    }

    public function testDoesntCreateSectionWhenProfilerHasFinished()
    {
        $section = new CaptureException(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Render
        );
        $server
            ->expects($this->never())
            ->method('create');
        $processes
            ->expects($this->never())
            ->method('execute');

        $section->start($identity = new Identity('profile-uuid'));
        $this->assertNull($section->finish($identity));
        $this->assertNull($section->capture(new \Exception));
    }

    public function testCreateSectionWhenProfilerStarted()
    {
        $section = new CaptureException(
            $server = $this->createMock(Server::class),
            $processes = $this->createMock(Processes::class),
            new Render
        );
        $e = new \Exception;
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->name() === 'api.section.exception' &&
                    $resource->properties()->contains('profile') &&
                    $resource->properties()->contains('graph') &&
                    $resource->properties()->get('profile')->value() === 'profile-uuid' &&
                    $resource->properties()->get('graph')->value() === '<graph-output/>';
            }));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command) use ($e): bool {
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
        $this->assertNull($section->capture($e));
    }
}

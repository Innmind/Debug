<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\CLI;

use Innmind\Debug\{
    CLI\CaptureAppGraph,
    Profiler\Section\CaptureAppGraph as Section,
    Profiler\Profile\Identity,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\Server\Processes;
use Innmind\ObjectGraph\Visualize;
use PHPUnit\Framework\TestCase;

class CaptureAppGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new CaptureAppGraph(
                $this->createMock(Command::class),
                new Section(
                    $this->createMock(Server::class),
                    $this->createMock(Processes::class),
                    new Visualize
                )
            )
        );
    }

    public function testStringCast()
    {
        $handle = new CaptureAppGraph(
            $inner = $this->createMock(Command::class),
            new Section(
                $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Visualize
            )
        );
        $inner
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('foo');

        $this->assertSame('foo', (string) $handle);
    }

    public function testCaptureInnerHandlerThatRepresentTheRealApp()
    {
        $handle = new CaptureAppGraph(
            $inner = $this->createMock(Command::class),
            $section = new Section(
                $server = $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Visualize
            )
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options);
        $server
            ->expects($this->once())
            ->method('create');

        $section->start(new Identity('profile-uuid'));

        $this->assertNull($handle($env, $arguments, $options));

        $section->finish(new Identity('profile-uuid'));
    }
}

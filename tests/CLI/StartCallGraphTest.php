<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\CLI;

use Innmind\Debug\{
    CLI\StartCallGraph,
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\Clock;
use PHPUnit\Framework\TestCase;

class StartCallGraphTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new StartCallGraph(
                $this->createMock(Command::class),
                new CallGraph(
                    new CaptureCallGraph(
                        $this->createMock(Server::class)
                    ),
                    $this->createMock(Clock::class)
                ),
                'Class fqcn'
            )
        );
    }

    public function testStringCast()
    {
        $command = new StartCallGraph(
            $inner = $this->createMock(Command::class),
            new CallGraph(
                new CaptureCallGraph(
                    $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            ),
            'Class fqcn'
        );
        $inner
            ->expects($this->once())
            ->method('toString')
            ->willReturn('foo');

        $this->assertSame('foo', $command->toString());
    }

    public function testSendGraph()
    {
        $handle = new StartCallGraph(
            $inner = $this->createMock(Command::class),
            new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            ),
            'Class fqcn'
        );
        $server
            ->expects($this->once())
            ->method('create');
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options);

        $this->assertNull($handle($env, $arguments, $options));
        $section->finish(new Identity('profile-uuid'));
    }

    public function testSendGraphWhenExceptionThrown()
    {
        $handle = new StartCallGraph(
            $inner = $this->createMock(Command::class),
            new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(Clock::class)
            ),
            'Class fqcn'
        );
        $server
            ->expects($this->once())
            ->method('create');
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options)
            ->will($this->throwException(new \Exception));

        try {
            $handle($env, $arguments, $options);
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $section->finish(new Identity('profile-uuid'));
        }
    }
}

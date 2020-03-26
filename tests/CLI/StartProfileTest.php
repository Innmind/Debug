<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\CLI;

use Innmind\Debug\{
    CLI\StartProfile,
    Profiler,
    Profiler\Profile\Identity,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
    Environment\ExitCode,
};
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class StartProfileTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new StartProfile(
                $this->createMock(Command::class),
                $this->createMock(Profiler::class)
            )
        );
    }

    public function testStringCast()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(Command::class),
            $this->createMock(Profiler::class)
        );
        $inner
            ->expects($this->once())
            ->method('toString')
            ->willReturn('foo');

        $this->assertSame('foo', $handle->toString());
    }

    public function testStart()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(Command::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $env
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('string', 'foo', 'bar', 'baz'));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options);
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('foo bar baz')
            ->willReturn(new Identity('some-uuid'));

        $this->assertNull($handle($env, $arguments, $options));
    }

    public function testFailWhenExceptionThrown()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(Command::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('string', 'foo', 'bar', 'baz'));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options)
            ->will($this->throwException(new \Exception));
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('foo bar baz')
            ->willReturn($identity = new Identity('some-uuid'));
        $profiler
            ->expects($this->once())
            ->method('fail')
            ->with($identity, '1');

        $this->expectException(\Exception::class);

        $handle($env, $arguments, $options);
    }

    public function testFailWhenExitCodeOverZero()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(Command::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('string', 'foo', 'bar', 'baz'));
        $env
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(127));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options);
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('foo bar baz')
            ->willReturn($identity = new Identity('some-uuid'));
        $profiler
            ->expects($this->once())
            ->method('fail')
            ->with($identity, '127');

        $this->assertNull($handle($env, $arguments, $options));
    }

    public function testSucceedWhenExitCodeIsZero()
    {
        $handle = new StartProfile(
            $inner = $this->createMock(Command::class),
            $profiler = $this->createMock(Profiler::class)
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('string', 'foo', 'bar', 'baz'));
        $env
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options);
        $profiler
            ->expects($this->once())
            ->method('start')
            ->with('foo bar baz')
            ->willReturn($identity = new Identity('some-uuid'));
        $profiler
            ->expects($this->once())
            ->method('succeed')
            ->with($identity, '0');

        $this->assertNull($handle($env, $arguments, $options));
    }
}

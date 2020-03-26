<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\CLI;

use Innmind\Debug\{
    CLI\CaptureException,
    Profiler\Section\CaptureException as Section,
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
use Innmind\StackTrace\Render;
use PHPUnit\Framework\TestCase;

class CaptureExceptionTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new CaptureException(
                $this->createMock(Command::class),
                new Section(
                    $this->createMock(Server::class),
                    $this->createMock(Processes::class),
                    new Render
                )
            )
        );
    }

    public function testStringCast()
    {
        $handle = new CaptureException(
            $inner = $this->createMock(Command::class),
            new Section(
                $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Render
            )
        );
        $inner
            ->expects($this->once())
            ->method('toString')
            ->willReturn('foo');

        $this->assertSame('foo', $handle->toString());
    }

    public function testDoNothingWhenNoExceptionThrown()
    {
        $handle = new CaptureException(
            $inner = $this->createMock(Command::class),
            $section = new Section(
                $server = $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Render
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
            ->expects($this->never())
            ->method('create');

        $section->start(new Identity('profile-uuid'));

        $this->assertNull($handle($env, $arguments, $options));
    }

    public function testCaptureThrownException()
    {
        $handle = new CaptureException(
            $inner = $this->createMock(Command::class),
            $section = new Section(
                $server = $this->createMock(Server::class),
                $this->createMock(Processes::class),
                new Render
            )
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options)
            ->will($this->throwException(new \Exception));
        $server
            ->expects($this->once())
            ->method('create');

        $section->start(new Identity('profile-uuid'));

        try {
            $handle($env, $arguments, $options);
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $section->finish(new Identity('profile-uuid'));
        }
    }
}

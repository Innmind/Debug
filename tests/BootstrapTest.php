<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug;

use function Innmind\Debug\bootstrap;
use Innmind\Debug\{
    HttpFramework,
    CLI,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;
use Innmind\HttpFramework\RequestHandler;
use Innmind\CLI\Command;
use Innmind\ObjectGraph\{
    Graph,
    Assert\Stack,
};
use Innmind\Immutable\StreamInterface;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testInterface()
    {
        $debug = bootstrap(
            Factory::build(),
            Url::fromString('http://localhost:8000/')
        );

        $this->assertInternalType('array', $debug);
        $this->assertInternalType('callable', $debug['http']);
        $this->assertInternalType('callable', $debug['cli']);

        $handler = $debug['http']($this->createMock(RequestHandler::class));
        $this->assertInstanceOf(RequestHandler::class, $handler);
        $stack = Stack::of(
            HttpFramework\StartProfile::class,
            HttpFramework\CaptureHttp::class,
            HttpFramework\CaptureException::class,
            HttpFramework\CaptureAppGraph::class
        );
        $this->assertTrue($stack((new Graph)($handler)));

        $commands = $debug['cli'](
            $this->createMock(Command::class),
            $this->createMock(Command::class)
        );
        $this->assertInstanceOf(StreamInterface::class, $commands);
        $this->assertSame(Command::class, (string) $commands->type());
        $this->assertCount(2, $commands);
        $command = $commands->first();
        $this->assertInstanceOf(Command::class, $command);
        $stack = Stack::of(
            CLI\StartProfile::class,
            CLI\CaptureException::class,
            CLI\CaptureAppGraph::class
        );
        $this->assertTrue($stack((new Graph)($command)));
    }
}

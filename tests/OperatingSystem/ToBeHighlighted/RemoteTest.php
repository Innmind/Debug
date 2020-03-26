<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\ToBeHighlighted;

use Innmind\Debug\{
    OperatingSystem\ToBeHighlighted\Remote,
    Profiler\Section\CaptureAppGraph\ToBeHighlighted,
};
use Innmind\OperatingSystem\Remote as RemoteInterface;
use Innmind\Server\Control\Server;
use Innmind\Url\{
    Url,
    Authority,
};
use Innmind\Socket\{
    Client,
    Internet\Transport,
};
use Innmind\HttpTransport\Transport as HttpTransport;
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testInterface()
    {
        $remote = new Remote(
            $this->createMock(RemoteInterface::class),
            new ToBeHighlighted
        );

        $this->assertInstanceOf(RemoteInterface::class, $remote);
        $this->assertInstanceOf(
            Server::class,
            $remote->ssh(Url::of('ssh://example.com'))
        );
        $this->assertInstanceOf(
            Client::class,
            $remote->socket(Transport::tcp(), Authority::none())
        );
        $this->assertInstanceOf(HttpTransport::class, $remote->http());
    }

    public function testHighlightHttpClient()
    {
        $remote = new Remote(
            $inner = $this->createMock(RemoteInterface::class),
            $toBeHighlighted = new ToBeHighlighted
        );
        $inner
            ->expects($this->once())
            ->method('http')
            ->willReturn($http = $this->createMock(HttpTransport::class));

        $this->assertFalse($toBeHighlighted->get()->contains($http));
        $this->assertSame($http, $remote->http());
        $this->assertTrue($toBeHighlighted->get()->contains($http));
    }
}

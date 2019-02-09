<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\{
    OperatingSystem\CallGraph\Remote,
    OperatingSystem\CallGraph\Remote\Http,
    OperatingSystem\Control,
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\OperatingSystem\Remote as RemoteInterface;
use Innmind\Rest\Client\Server;
use Innmind\Server\Control\{
    Server as ServerControl,
    Server\Command,
};
use Innmind\Url\{
    UrlInterface,
    Url,
    AuthorityInterface,
};
use Innmind\Socket\{
    Client,
    Internet\Transport,
};
use Innmind\HttpTransport\Transport as HttpTransport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testInterface()
    {
        $remote = new Remote(
            $this->createMock(RemoteInterface::class),
            new CallGraph(
                new CaptureCallGraph(
                    $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );

        $this->assertInstanceOf(RemoteInterface::class, $remote);
        $this->assertInstanceOf(
            ServerControl::class,
            $remote->ssh($this->createMock(UrlInterface::class))
        );
        $this->assertInstanceOf(
            Client::class,
            $remote->socket(Transport::tcp(), $this->createMock(AuthorityInterface::class))
        );
        $this->assertInstanceOf(Http::class, $remote->http());
        $this->assertSame($remote->http(), $remote->http());
    }
}
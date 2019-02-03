<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Remote;

use Innmind\Debug\{
    OperatingSystem\Remote\Server,
    OperatingSystem\Remote\Server\Processes,
    OperatingSystem\Control\RenderProcess\Remote,
    OperatingSystem\Control\RenderProcess\Local,
    OperatingSystem\Control\Processes\State,
    Profiler\Section\CaptureProcesses,
    Profiler\Profile\Identity,
};
use Innmind\Server\Control\{
    Server as ServerInterface,
    Server\Command,
};
use Innmind\Rest\Client\Server as RestServer;
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testInterface()
    {
        $render = new Remote(
            new Local
        );
        $server = new Server(
            $this->createMock(ServerInterface::class),
            Url::fromString('ssh://user:pwd@example.com:2242/'),
            $render,
            $state = new State(
                $render,
                CaptureProcesses::remote(
                    $profiler = $this->createMock(RestServer::class)
                )
            )
        );
        $profiler
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('processes')->value()->size() === 1 &&
                    $resource->properties()->get('processes')->value()->current() === "ssh: user:pwd@example.com:2242\n[background] echo\n";
            }));

        $this->assertInstanceOf(ServerInterface::class, $server);
        $this->assertInstanceOf(Processes::class, $server->processes());
        $this->assertSame($server->processes(), $server->processes());
        $server->processes()->execute(Command::background('echo'));
        $state->finish(new Identity('profile-uuid'));
    }
}

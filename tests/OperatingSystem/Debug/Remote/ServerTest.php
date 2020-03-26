<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Debug\Remote;

use Innmind\Debug\{
    OperatingSystem\Debug\Remote\Server,
    OperatingSystem\Debug\Remote\Server\Processes,
    OperatingSystem\Debug\Control\RenderProcess\Remote,
    OperatingSystem\Debug\Control\RenderProcess\Local,
    OperatingSystem\Debug\Control\Processes\State,
    Profiler\Section\CaptureProcesses,
    Profiler\Profile\Identity,
};
use Innmind\Server\Control\{
    Server as ServerInterface,
    Server\Command,
};
use Innmind\Rest\Client\Server as RestServer;
use Innmind\Url\Url;
use function Innmind\Immutable\first;
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
            Url::of('ssh://user:pwd@example.com:2242/'),
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
                    first($resource->properties()->get('processes')->value()) === "ssh: user:pwd@example.com:2242\n[background] echo\n";
            }));

        $this->assertInstanceOf(ServerInterface::class, $server);
        $this->assertInstanceOf(Processes::class, $server->processes());
        $this->assertSame($server->processes(), $server->processes());
        $server->processes()->execute(Command::background('echo'));
        $state->finish(new Identity('profile-uuid'));
    }
}

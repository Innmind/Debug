<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote;

use Innmind\Debug\{
    Recorder\Beacon,
    OperatingSystem\Remote\Ssh\Processes as Debug,
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\Url\Url;
use Innmind\Immutable\Either;

final class Ssh implements Server
{
    private Server $inner;
    private Beacon $beacon;
    private Url $server;

    private function __construct(Server $inner, Beacon $beacon, Url $server)
    {
        $this->inner = $inner;
        $this->beacon = $beacon;
        $this->server = $server;
    }

    public static function of(Server $inner, Beacon $beacon, Url $server): self
    {
        return new self($inner, $beacon, $server);
    }

    public function processes(): Processes
    {
        return Debug::of($this->inner->processes(), $this->beacon, $this->server);
    }

    public function volumes(): Volumes
    {
        return $this->inner->volumes();
    }

    public function reboot(): Either
    {
        return $this->inner->reboot();
    }

    public function shutdown(): Either
    {
        return $this->inner->shutdown();
    }
}

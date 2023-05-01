<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\{
    Recorder\Beacon,
    OperatingSystem\Remote\Http,
    OperatingSystem\Remote\Sql,
    OperatingSystem\Remote\Ssh,
};
use Innmind\OperatingSystem\Remote as RemoteInterface;
use Innmind\Server\Control\Server;
use Innmind\Socket\{
    Internet\Transport,
    Client,
};
use Innmind\Url\{
    Url,
    Authority,
};
use Innmind\HttpTransport\Transport as HttpTransport;
use Innmind\Immutable\Maybe;
use Formal\AccessLayer\Connection;

/**
 * @internal
 */
final class Remote implements RemoteInterface
{
    private RemoteInterface $inner;
    private Beacon $beacon;

    private function __construct(RemoteInterface $inner, Beacon $beacon)
    {
        $this->inner = $inner;
        $this->beacon = $beacon;
    }

    public static function of(RemoteInterface $inner, Beacon $beacon): self
    {
        return new self($inner, $beacon);
    }

    public function ssh(Url $server): Server
    {
        return Ssh::of($this->inner->ssh($server), $this->beacon, $server);
    }

    public function socket(Transport $transport, Authority $authority): Maybe
    {
        return $this->inner->socket($transport, $authority);
    }

    public function http(): HttpTransport
    {
        return Http::of($this->inner->http(), $this->beacon);
    }

    public function sql(Url $server): Connection
    {
        return Sql::of($this->inner->sql($server), $this->beacon);
    }
}

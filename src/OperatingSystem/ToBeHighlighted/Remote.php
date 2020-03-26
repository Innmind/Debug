<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\ToBeHighlighted;

use Innmind\Debug\Profiler\Section\CaptureAppGraph\ToBeHighlighted;
use Innmind\OperatingSystem\Remote as RemoteInterface;
use Innmind\Server\Control\Server;
use Innmind\Socket\{
    Internet\Transport,
    Client,
};
use Innmind\Url\{
    UrlInterface,
    AuthorityInterface,
};
use Innmind\HttpTransport\Transport as HttpTransport;

final class Remote implements RemoteInterface
{
    private RemoteInterface $remote;
    private ToBeHighlighted $toBeHighlighted;

    public function __construct(
        RemoteInterface $remote,
        ToBeHighlighted $toBeHighlighted
    ) {
        $this->remote = $remote;
        $this->toBeHighlighted = $toBeHighlighted;
    }

    public function ssh(UrlInterface $server): Server
    {
        return $this->remote->ssh($server);
    }

    public function socket(Transport $transport, AuthorityInterface $authority): Client
    {
        return $this->remote->socket($transport, $authority);
    }

    public function http(): HttpTransport
    {
        $http = $this->remote->http();
        $this->toBeHighlighted->add($http);

        return $http;
    }
}

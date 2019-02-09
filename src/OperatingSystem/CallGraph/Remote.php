<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\CallGraph;
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
    private $remote;
    private $graph;
    private $http;

    public function __construct(
        RemoteInterface $remote,
        CallGraph $graph
    ) {
        $this->remote = $remote;
        $this->graph = $graph;
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
        return $this->http ?? $this->http = new Remote\Http(
            $this->remote->http(),
            $this->graph
        );
    }
}

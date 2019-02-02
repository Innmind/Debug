<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\Profiler\{
    Section\Remote\CaptureHttp,
    Section,
    Profile\Identity,
};
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

final class Remote implements RemoteInterface, Section
{
    private $remote;
    private $captureHttp;
    private $http;

    public function __construct(
        RemoteInterface $remote,
        CaptureHttp $captureHttp
    ) {
        $this->remote = $remote;
        $this->captureHttp = $captureHttp;
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
            $this->captureHttp
        );
    }

    public function start(Identity $identity): void
    {
        $this->captureHttp->start($identity);
    }

    public function finish(Identity $identity): void
    {
        $this->captureHttp->finish($identity);
    }
}

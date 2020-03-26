<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug;

use Innmind\Debug\Profiler\Section\Remote\CaptureHttp;
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
    private CaptureHttp $captureHttp;
    private Control\RenderProcess\Remote $render;
    private Control\Processes\State $remoteProcesses;
    private ?Remote\Http $http = null;

    public function __construct(
        RemoteInterface $remote,
        CaptureHttp $captureHttp,
        Control\RenderProcess\Remote $render,
        Control\Processes\State $remoteProcesses
    ) {
        $this->remote = $remote;
        $this->captureHttp = $captureHttp;
        $this->render = $render;
        $this->remoteProcesses = $remoteProcesses;
    }

    public function ssh(UrlInterface $server): Server
    {
        return new Remote\Server(
            $this->remote->ssh($server),
            $server,
            $this->render,
            $this->remoteProcesses
        );
    }

    public function socket(Transport $transport, AuthorityInterface $authority): Client
    {
        return $this->remote->socket($transport, $authority);
    }

    public function http(): HttpTransport
    {
        return $this->http ??= new Remote\Http(
            $this->remote->http(),
            $this->captureHttp
        );
    }
}

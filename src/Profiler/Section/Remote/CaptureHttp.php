<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Section\Remote;

use Innmind\Debug\Profiler\{
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\{
    Server,
    HttpResource,
    HttpResource\Property,
    Identity as RestIdentity,
};

final class CaptureHttp implements Section
{
    private Server $server;
    private ?Identity $identity = null;
    private ?RestIdentity $section = null;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function start(Identity $identity): void
    {
        $this->identity = $identity;
        $this->section = null;
    }

    public function capture(string $request, string $response): void
    {
        if (\is_null($this->identity)) {
            return;
        }

        if (\is_null($this->section)) {
            $this->section = $this->server->create(HttpResource::of(
                'api.section.remote.http',
                new Property('profile', $this->identity->toString()),
                new Property('request', $request),
                new Property('response', $response)
            ));

            return;
        }

        $this->server->update(
            $this->section,
            HttpResource::of(
                'api.section.remote.http',
                new Property('profile', $this->identity->toString()),
                new Property('request', $request),
                new Property('response', $response),
            ),
        );
    }

    public function finish(Identity $identity): void
    {
        $this->identity = null;
        $this->section = null;
    }
}

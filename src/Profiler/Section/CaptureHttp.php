<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Section;

use Innmind\Debug\Profiler\{
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\{
    Server,
    HttpResource,
    HttpResource\Property,
};

final class CaptureHttp implements Section
{
    private $server;
    private $profile;
    private $identity;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function start(Identity $identity): void
    {
        $this->profile = $identity;
    }

    public function received(string $request): void
    {
        if (\is_null($this->profile)) {
            return;
        }

        $this->identity = $this->server->create(HttpResource::of(
            'api.section.http',
            new Property('request', $request),
            new Property('profile', (string) $this->profile)
        ));
    }

    public function respondedWith(string $response): void
    {
        if (\is_null($this->identity)) {
            return;
        }

        $this->server->update(
            $this->identity,
            HttpResource::of(
                'api.section.http',
                new Property('response', $response)
            )
        );
    }

    public function finish(Identity $identity): void
    {
        $this->profile = null;
        $this->identity = null;
    }
}

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
};
use Innmind\Immutable\Sequence;

final class CaptureHttp implements Section
{
    private Server $server;
    private Sequence $pairs;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->pairs = Sequence::of('array');
    }

    public function start(Identity $identity): void
    {
        $this->pairs = $this->pairs->clear();
    }

    public function capture(string $request, string $response): void
    {
        $this->pairs = $this->pairs->add([$request, $response]);
    }

    public function finish(Identity $identity): void
    {
        if ($this->pairs->empty()) {
            return;
        }

        [$request, $response] = $this->pairs->first();
        $section = $this->server->create(HttpResource::of(
            'api.section.remote.http',
            new Property('profile', $identity->toString()),
            new Property('request', $request),
            new Property('response', $response)
        ));
        $this
            ->pairs
            ->drop(1)
            ->foreach(function(array $pair) use ($identity, $section): void {
                [$request, $response] = $pair;

                $this->server->update(
                    $section,
                    HttpResource::of(
                        'api.section.remote.http',
                        new Property('profile', $identity->toString()),
                        new Property('request', $request),
                        new Property('response', $response)
                    )
                );
            });
    }
}

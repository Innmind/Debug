<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler;

use Innmind\Debug\{
    Profiler,
    Profiler\Profile\Identity,
};
use Innmind\Rest\Client\{
    Server,
    HttpResource,
    HttpResource\Property,
    Identity\Identity as RestIdentity,
};
use Innmind\TimeContinuum\{
    Clock,
    Earth\Format\ISO8601,
};
use Innmind\Immutable\Set;

final class Http implements Profiler
{
    private Server $server;
    private Clock $clock;
    /** @var Set<Section> */
    private Set $sections;

    public function __construct(
        Server $server,
        Clock $clock,
        Section ...$sections
    ) {
        $this->server = $server;
        $this->clock = $clock;
        /** @var Set<Section> */
        $this->sections = Set::of(Section::class, ...$sections);
    }

    public function start(string $name): Identity
    {
        $identity = $this->server->create(HttpResource::of(
            'api.profile',
            new Property('name', $name),
            new Property('started_at', $this->clock->now()->format(new ISO8601)),
        ));
        $identity = new Identity($identity->toString());

        $this->sections->foreach(static function(Section $section) use ($identity): void {
            $section->start($identity);
        });

        return $identity;
    }

    public function fail(Identity $identity, string $exit): void
    {
        $this->finish($identity, $exit, false);
    }

    public function succeed(Identity $identity, string $exit): void
    {
        $this->finish($identity, $exit, true);
    }

    private function finish(Identity $identity, string $exit, bool $success): void
    {
        $this->sections->foreach(static function(Section $section) use ($identity): void {
            $section->finish($identity);
        });

        $this->server->update(
            new RestIdentity($identity->toString()),
            HttpResource::of(
                'api.profile',
                new Property('success', $success),
                new Property('exit', $exit),
            ),
        );
    }
}

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
    TimeContinuumInterface,
    Format\ISO8601,
};
use Innmind\Immutable\Set;

final class Http implements Profiler
{
    private $server;
    private $clock;
    private $sections;

    public function __construct(
        Server $server,
        TimeContinuumInterface $clock,
        Section ...$sections
    ) {
        $this->server = $server;
        $this->clock = $clock;
        $this->sections = Set::of(Section::class, ...$sections);
    }

    public function start(string $name): Identity
    {
        $identity = $this->server->create(HttpResource::of(
            'api.profile',
            new Property('name', $name),
            new Property('started_at', $this->clock->now()->format(new ISO8601))
        ));
        $identity = new Identity((string) $identity);

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
            new RestIdentity((string) $identity),
            HttpResource::of(
                'api.profile',
                new Property('success', $success),
                new Property('exit', $exit)
            )
        );
    }
}

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
use Innmind\Immutable\Set;
use function Innmind\Immutable\assertSet;

final class CaptureEnvironment implements Section
{
    private Server $server;
    private Set $environment;

    public function __construct(Server $server, Set $environment)
    {
        assertSet('string', $environment, 2);

        $this->server = $server;
        $this->environment = $environment;
    }

    public function start(Identity $identity): void
    {
        // nothing to do
    }

    public function finish(Identity $identity): void
    {
        if ($this->environment->empty()) {
            return;
        }

        $this->server->create(HttpResource::of(
            'api.section.environment',
            new Property('pairs', $this->environment),
            new Property('profile', $identity->toString()),
        ));
    }
}

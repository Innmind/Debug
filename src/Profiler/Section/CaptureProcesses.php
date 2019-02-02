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

final class CaptureProcesses implements Section
{
    private $server;
    private $processes;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->processes = Set::of('string');
    }

    public function start(Identity $identity): void
    {
        // nothing to do
    }

    public function capture(string $process): void
    {
        $this->processes = $this->processes->add($process);
    }

    public function finish(Identity $identity): void
    {
        $this->server->create(HttpResource::of(
            'api.section.processes',
            new Property('processes', $this->processes),
            new Property('profile', (string) $identity)
        ));
    }
}

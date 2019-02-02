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
    private $resource;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->processes = Set::of('string');
        $this->resource = 'api.section.processes';
    }

    public static function remote(Server $server): self
    {
        $self = new self($server);
        $self->resource = 'api.section.remote.processes';

        return $self;
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
        if ($this->processes->empty()) {
            return;
        }

        $this->server->create(HttpResource::of(
            $this->resource,
            new Property('processes', $this->processes),
            new Property('profile', (string) $identity)
        ));
    }
}

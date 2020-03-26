<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Section;

use Innmind\Debug\{
    Profiler\Section,
    Profiler\Profile\Identity,
    CallGraph\Node,
};
use Innmind\Rest\Client\{
    Server,
    HttpResource,
    HttpResource\Property,
};
use Innmind\Json\Json;

final class CaptureCallGraph implements Section
{
    private Server $server;
    private ?array $graph = null;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function start(Identity $identity): void
    {
        $this->graph = null;
    }

    public function capture(Node $graph): void
    {
        $this->graph = $graph->normalize();
    }

    public function finish(Identity $identity): void
    {
        if (\is_null($this->graph)) {
            return;
        }

        $this->server->create(HttpResource::of(
            'api.section.call_graph',
            new Property('graph', Json::encode($this->graph)),
            new Property('profile', $identity->toString()),
        ));
    }
}

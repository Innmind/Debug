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
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\ObjectGraph\{
    Visualize,
    Graph,
};

final class CaptureAppGraph implements Section
{
    private $server;
    private $processes;
    private $render;
    private $graph;
    private $profile;

    public function __construct(
        Server $server,
        Processes $processes,
        Visualize $render
    ) {
        $this->server = $server;
        $this->processes = $processes;
        $this->render = $render;
        $this->graph = new Graph;
    }

    public function start(Identity $identity): void
    {
        $this->profile = $identity;
    }

    public function capture(object $app): void
    {
        if (\is_null($this->profile)) {
            return;
        }

        $this->server->create(HttpResource::of(
            'api.section.app_graph',
            new Property('profile', (string) $this->profile),
            new Property(
                'graph',
                (string) $this
                    ->processes
                    ->execute(
                        Command::foreground('dot')
                            ->withShortOption('Tsvg')
                            ->withInput(
                                ($this->render)(
                                    ($this->graph)($app)
                                )
                            )
                    )
                    ->wait()
                    ->output()
            )
        ));
    }

    public function finish(Identity $identity): void
    {
        $this->profile = null;
    }
}

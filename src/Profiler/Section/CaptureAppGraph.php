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
    Visitor\FlagDependencies,
    Visitor\RemoveDependenciesSubGraph,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};
use function Innmind\Immutable\assertSet;

final class CaptureAppGraph implements Section
{
    private $server;
    private $processes;
    private $render;
    private $flagDependencies;
    private $removeDependencies;
    private $graph;
    private $profile;
    private $app;

    public function __construct(
        Server $server,
        Processes $processes,
        Visualize $render,
        SetInterface $dependencies = null
    ) {
        $dependencies = $dependencies ?? Set::of('object');
        assertSet('object', $dependencies, 4);

        $this->server = $server;
        $this->processes = $processes;
        $this->render = $render;
        $this->flagDependencies = new FlagDependencies(...$dependencies);
        $this->removeDependencies = new RemoveDependenciesSubGraph;
        $this->graph = new Graph;
    }

    public function start(Identity $identity): void
    {
        $this->profile = $identity;
        $this->app = null;
    }

    public function capture(object $app): void
    {
        $this->app = $app;
    }

    public function finish(Identity $identity): void
    {
        if (\is_null($this->profile) || \is_null($this->app)) {
            return;
        }

        $graph = ($this->graph)($this->app);
        ($this->flagDependencies)($graph);
        ($this->removeDependencies)($graph);

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
                                ($this->render)($graph)
                            )
                    )
                    ->wait()
                    ->output()
            )
        ));
        $this->profile = null;
        $this->app = null;
    }
}

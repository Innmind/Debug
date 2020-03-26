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
    Node,
    Visitor\FlagDependencies,
    Visitor\RemoveDependenciesSubGraph,
    Visitor\AccessObjectNode,
    Exception\ObjectNotFound,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\{
    assertSet,
    unwrap,
};

final class CaptureAppGraph implements Section
{
    private Server $server;
    private Processes $processes;
    private Visualize $render;
    private CaptureAppGraph\ToBeHighlighted $toBeHighlighted;
    private Set $dependencies;
    private FlagDependencies $flagDependencies;
    private RemoveDependenciesSubGraph $removeDependencies;
    private Graph $graph;
    private ?Identity $profile = null;
    private ?object $app = null;

    public function __construct(
        Server $server,
        Processes $processes,
        Visualize $render,
        CaptureAppGraph\ToBeHighlighted $toBeHighlighted = null,
        Set $dependencies = null
    ) {
        $toBeHighlighted ??= new CaptureAppGraph\ToBeHighlighted;
        $dependencies ??= Set::of('object');
        assertSet('object', $dependencies, 4);

        $this->server = $server;
        $this->processes = $processes;
        $this->render = $render;
        $this->toBeHighlighted = $toBeHighlighted;
        $this->dependencies = $dependencies;
        $this->flagDependencies = new FlagDependencies(...unwrap($dependencies));
        $this->removeDependencies = new RemoveDependenciesSubGraph;
        $this->graph = new Graph;
    }

    public function start(Identity $identity): void
    {
        $this->profile = $identity;
        $this->app = null;
        $this->toBeHighlighted->clear();
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
        $this->highlight($graph);

        $process = $this->processes->execute(
            Command::foreground('dot')
                ->withShortOption('Tsvg')
                ->withInput(
                    ($this->render)($graph)
                )
        );
        $process->wait();

        $this->server->create(HttpResource::of(
            'api.section.app_graph',
            new Property('profile', (string) $this->profile),
            new Property(
                'graph',
                $process->output()->toString(),
            )
        ));
        $this->profile = null;
        $this->app = null;
    }

    private function highlight(Node $graph): void
    {
        $dependencies = $this
            ->dependencies
            ->intersect($this->toBeHighlighted->get());
        // paths to the dependencies is highlighted only if an object calling it
        // has been highlighted to avoid highlighting too many paths leading to
        // the dependency
        $toBeHighlighted = $this->toBeHighlighted->get()->diff($this->dependencies);
        $toBeHighlighted->foreach(static function(object $object) use ($graph): void {
            $graph->highlightPathTo($object);
        });
        $toBeHighlighted->foreach(static function(object $object) use ($dependencies, $graph): void {
            try {
                $node = (new AccessObjectNode($object))($graph);
            } catch (ObjectNotFound $e) {
                return;
            }

            $dependencies->foreach(static function(object $dependency) use ($node): void {
                $node->highlightPathTo($dependency);
            });
        });
    }
}

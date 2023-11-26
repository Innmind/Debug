<?php
declare(strict_types = 1);

namespace Innmind\Debug\Recorder;

use Innmind\Debug\{
    Recorder,
    Record,
    IDE,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\Server\Command;
use Innmind\Filesystem\File\Content;
use Innmind\ObjectGraph\{
    Lookup,
    Render,
    RewriteLocation\SublimeHandler,
    Graph,
};
use Innmind\DI\Container;
use Innmind\Immutable\Map;

/**
 * @internal
 */
final class AppGraph implements Recorder
{
    private OperatingSystem $os;
    /** @var Map<non-empty-string, string> */
    private Map $env;
    private Record $record;
    private Render $render;
    private Lookup $lookup;

    /**
     * @param Map<non-empty-string, string> $env
     */
    public function __construct(
        OperatingSystem $os,
        Map $env,
        IDE $ide,
    ) {
        $this->os = $os;
        $this->env = ($env)('X_INNMIND_DEBUG', 'true');
        $this->record = new Record\Nothing;
        $this->render = Render::of(match ($ide) {
            IDE::sublimeText => new SublimeHandler,
            default => null,
        })->fromTopToBottom();
        $this->lookup = Lookup::of();
    }

    public function __invoke(object $root): void
    {
        $graph = ($this->lookup)($root);
        $graph = $this->clean($graph);
        $svg = $this
            ->os
            ->control()
            ->processes()
            ->execute(
                Command::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withEnvironments($this->env)
                    ->withInput(($this->render)($graph)),
            )
            ->wait()
            ->match(
                static fn($success) => Content::ofChunks(
                    $success
                        ->output()
                        ->chunks()
                        ->map(static fn($pair) => $pair[0]),
                ),
                static fn() => Content::ofString('Unable to render the app graph'),
            );
        ($this->record)(
            static fn($mutation) => $mutation
                ->sections()
                ->appGraph()
                ->record($svg),
        );
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }

    private function clean(Graph $graph): Graph
    {
        if ($graph->root()->class()->toString() !== Container::class) {
            return $graph;
        }

        $children = $graph->nodes()->remove($graph->root());
        $unwanted = $graph
            ->root()
            ->relations()
            ->filter(static fn($relation) => $relation->property()->toString() !== 'services')
            ->map(static fn($relation) => $relation->reference());
        $toRemove = $children
            ->filter(static fn($node) => $unwanted->any(
                static fn($reference) => $reference->equals($node->reference()),
            ))
            ->flatMap(static fn($node) => $node->relations())
            ->map(static fn($relation) => $relation->reference())
            ->merge($unwanted);
        $children = $children->filter(static fn($node) => !$toRemove->any(
            static fn($reference) => $reference->equals($node->reference()),
        ));

        return Graph::of(
            $graph
                ->root()
                ->filterRelation(static fn($relation) => $relation->property()->toString() === 'services'),
            $children,
        );
    }
}

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
};
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
        $graph = $this
            ->os
            ->control()
            ->processes()
            ->execute(
                Command::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withEnvironments($this->env)
                    ->withInput(($this->render)(($this->lookup)($root))),
            )
            ->wait()
            ->match(
                static fn($success) => Content\Chunks::of(
                    $success
                        ->output()
                        ->chunks()
                        ->map(static fn($pair) => $pair[0]),
                ),
                static fn() => Content\Lines::ofContent('Unable to render the app graph'),
            );
        ($this->record)(
            static fn($mutation) => $mutation
                ->sections()
                ->appGraph()
                ->record($graph),
        );
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

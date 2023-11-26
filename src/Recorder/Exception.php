<?php
declare(strict_types = 1);

namespace Innmind\Debug\Recorder;

use Innmind\Debug\{
    Recorder,
    Record,
    IDE,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Filesystem\File\Content;
use Innmind\Server\Control\Server\Command;
use Innmind\StackTrace\{
    StackTrace,
    Render,
    Link\SublimeHandler,
    FormatPath,
};
use Innmind\Immutable\Map;

/**
 * @internal
 */
final class Exception implements Recorder
{
    private OperatingSystem $os;
    /** @var Map<non-empty-string, string> */
    private Map $env;
    private Record $record;
    private Render $render;

    /**
     * @param Map<non-empty-string, string> $env
     */
    public function __construct(
        OperatingSystem $os,
        Map $env,
        IDE $ide,
        FormatPath $formatPath,
    ) {
        $this->os = $os;
        $this->env = ($env)('X_INNMIND_DEBUG', 'true');
        $this->record = new Record\Nothing;
        $this->render = Render::of(
            match ($ide) {
                IDE::sublimeText => new SublimeHandler,
                default => null,
            },
            $formatPath,
        );
    }

    public function __invoke(\Throwable $e): void
    {
        $graph = $this
            ->os
            ->control()
            ->processes()
            ->execute(
                Command::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withEnvironments($this->env)
                    ->withInput(($this->render)(StackTrace::of($e))),
            )
            ->wait()
            ->match(
                static fn($success) => Content::ofChunks(
                    $success
                        ->output()
                        ->chunks()
                        ->map(static fn($pair) => $pair[0]),
                ),
                static fn() => Content::ofString('Unable to render the exception graph'),
            );
        ($this->record)(
            static fn($mutation) => $mutation
                ->sections()
                ->exception()
                ->record($graph),
        );
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

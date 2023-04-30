<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
    IDE,
};
use Innmind\Framework\Http\RequestHandler;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Filesystem\File\Content;
use Innmind\Server\Control\Server\Command;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\StackTrace\{
    StackTrace,
    Render,
    Link\SublimeHandler,
};
use Innmind\Immutable\Map;

/**
 * @internal
 */
final class RecordException implements RequestHandler, Recorder
{
    private RequestHandler $inner;
    private OperatingSystem $os;
    /** @var Map<non-empty-string, string> */
    private Map $env;
    private Record $record;
    private Render $render;

    /**
     * @param Map<non-empty-string, string> $env
     */
    public function __construct(
        RequestHandler $inner,
        OperatingSystem $os,
        Map $env,
        IDE $ide,
    ) {
        $this->inner = $inner;
        $this->os = $os;
        $this->env = $env;
        $this->record = new Record\Nothing;
        $this->render = Render::of(match ($ide) {
            IDE::sublimeText => new SublimeHandler,
            default => null,
        });
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            return ($this->inner)($request);
        } catch (\Throwable $e) {
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
                    static fn($success) => Content\Chunks::of(
                        $success
                            ->output()
                            ->chunks()
                            ->map(static fn($pair) => $pair[0]),
                    ),
                    static fn() => Content\Lines::ofContent('Unable to render the exception graph'),
                );
            ($this->record)(
                static fn($mutation) => $mutation
                    ->sections()
                    ->exception()
                    ->record($graph),
            );

            throw $e;
        }
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

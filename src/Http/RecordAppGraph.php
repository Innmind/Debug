<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\Http\RequestHandler;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\Server\Command;
use Innmind\Filesystem\File\Content;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\ObjectGraph\{
    Lookup,
    Render,
};

/**
 * @internal
 */
final class RecordAppGraph implements RequestHandler, Recorder
{
    private RequestHandler $inner;
    private OperatingSystem $os;
    private Record $record;
    private Render $render;
    private Lookup $lookup;

    public function __construct(RequestHandler $inner, OperatingSystem $os)
    {
        $this->inner = $inner;
        $this->os = $os;
        $this->record = new Record\Nothing;
        $this->render = Render::of();
        $this->lookup = Lookup::of();
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = ($this->inner)($request);
        ($this->record)(
            fn($mutation) => $mutation
                ->sections()
                ->appGraph()
                ->record(
                    $this
                        ->os
                        ->control()
                        ->processes()
                        ->execute(
                            Command::foreground('dot')
                                ->withShortOption('Tsvg')
                                ->withInput(($this->render)(($this->lookup)($this->inner))),
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
                        ),
                ),
        );

        return $response;
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

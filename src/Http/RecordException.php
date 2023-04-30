<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\Http\RequestHandler;
use Innmind\Filesystem\File\Content;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

/**
 * @internal
 */
final class RecordException implements RequestHandler, Recorder
{
    private Record $record;
    private RequestHandler $inner;

    public function __construct(
        Record $record,
        RequestHandler $inner,
    ) {
        $this->record = $record;
        $this->inner = $inner;
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            return ($this->inner)($request);
        } catch (\Throwable $e) {
            ($this->record)(
                static fn($mutation) => $mutation
                    ->sections()
                    ->exception()
                    ->record(Content\None::of()),
            );

            throw $e;
        }
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

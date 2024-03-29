<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\Http\RequestHandler;
use Innmind\Http\{
    ServerRequest,
    Response,
};

/**
 * @internal
 */
final class RecordCall implements RequestHandler, Recorder
{
    private Record $record;
    private RequestHandler $inner;

    public function __construct(RequestHandler $inner)
    {
        $this->record = new Record\Nothing;
        $this->inner = $inner;
    }

    public function __invoke(ServerRequest $request): Response
    {
        ($this->record)(
            static fn($mutation) => $mutation
                ->sections()
                ->http()
                ->received(ServerRequest\Stringable::new()($request)),
        );
        $response = ($this->inner)($request);
        ($this->record)(
            static fn($mutation) => $mutation
                ->sections()
                ->http()
                ->respondedWith(Response\Stringable::new()($response)),
        );

        return $response;
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

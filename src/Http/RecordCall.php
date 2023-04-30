<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\Http\RequestHandler;
use Innmind\Http\Message\{
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

    public function __construct(Record $record, RequestHandler $inner)
    {
        $this->record = $record;
        $this->inner = $inner;
    }

    public function __invoke(ServerRequest $request): Response
    {
        ($this->record)(
            static fn($mutation) => $mutation
                ->sections()
                ->http()
                ->received(ServerRequest\Stringable::of($request)->asContent()),
        );
        $response = ($this->inner)($request);
        ($this->record)(
            static fn($mutation) => $mutation
                ->sections()
                ->http()
                ->respondedWith(Response\Stringable::of($response)->asContent()),
        );

        return $response;
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

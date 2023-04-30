<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\Recorder\AppGraph;
use Innmind\Framework\Http\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

/**
 * @internal
 */
final class RecordAppGraph implements RequestHandler
{
    private RequestHandler $inner;
    private AppGraph $record;

    public function __construct(RequestHandler $inner, AppGraph $record)
    {
        $this->inner = $inner;
        $this->record = $record;
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = ($this->inner)($request);

        ($this->record)($this->inner);

        return $response;
    }
}

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
        try {
            return ($this->inner)($request);
        } finally {
            ($this->record)($this->inner);
        }
    }
}

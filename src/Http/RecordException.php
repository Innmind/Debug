<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\Recorder\Exception;
use Innmind\Framework\Http\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

/**
 * @internal
 */
final class RecordException implements RequestHandler
{
    private RequestHandler $inner;
    private Exception $record;

    public function __construct(RequestHandler $inner, Exception $record)
    {
        $this->inner = $inner;
        $this->record = $record;
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            return ($this->inner)($request);
        } catch (\Throwable $e) {
            ($this->record)($e);

            throw $e;
        }
    }
}

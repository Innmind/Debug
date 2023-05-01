<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\Recorder\AppGraph;
use Innmind\Framework\Http\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\DI\Container;

/**
 * @internal
 */
final class RecordAppGraph implements RequestHandler
{
    private RequestHandler $inner;
    private AppGraph $record;
    private Container $container;

    public function __construct(
        RequestHandler $inner,
        AppGraph $record,
        Container $container,
    ) {
        $this->inner = $inner;
        $this->record = $record;
        $this->container = $container;
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            return ($this->inner)($request);
        } finally {
            ($this->record)($this->container);
        }
    }
}

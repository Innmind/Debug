<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\{
    Http\RequestHandler,
    Environment,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

/**
 * @internal
 */
final class RecordEnvironment implements RequestHandler, Recorder
{
    private Record $record;
    private RequestHandler $inner;
    private Environment $env;

    public function __construct(
        RequestHandler $inner,
        Environment $env,
    ) {
        $this->record = new Record\Nothing;
        $this->inner = $inner;
        $this->env = $env;
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            return ($this->inner)($request);
        } catch (\Throwable $e) {
            ($this->record)(
                fn($mutation) => $mutation
                    ->sections()
                    ->environment()
                    ->record($this->env->all()),
            );

            throw $e;
        }
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

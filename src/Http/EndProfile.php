<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Router\Component;
use Innmind\Http\{
    ServerRequest,
    Response,
};

/**
 * @internal
 * @psalm-immutable
 * @implements Component\Provider<Response, Response>
 */
final class EndProfile implements Recorder, Component\Provider
{
    private function __construct(
        private Record $record = new Record\Nothing,
    ) {
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }

    #[\Override]
    public function push(Record $record): void
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->record = $record;
    }

    #[\Override]
    public function toComponent(): Component
    {
        return Component::of(
            fn(ServerRequest $request, Response $response) => ($this->record)(
                static fn($mutation) => $mutation
                    ->sections()
                    ->http()
                    ->respondedWith(Response\Stringable::new()($response)),
            )
                ->flatMap(
                    fn() => ($this->record)(
                        static fn($mutation) => match ($response->statusCode()->successful()) {
                            true => $mutation->succeed($response->statusCode()->toString()),
                            false => $mutation->fail($response->statusCode()->toString()),
                        },
                    ),
                )
                ->map(static fn() => $response),
        );
    }
}

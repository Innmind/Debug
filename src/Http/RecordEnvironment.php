<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\Environment;
use Innmind\Router\Component;
use Innmind\Http\ServerRequest;
use Innmind\Immutable\SideEffect;

/**
 * @internal
 * @psalm-immutable
 * @implements Component\Provider<SideEffect, SideEffect>
 */
final class RecordEnvironment implements Recorder, Component\Provider
{
    private function __construct(
        private Environment $env,
        private Record $record = new Record\Nothing,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Environment $env): self
    {
        return new self($env);
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
            fn(ServerRequest $request, SideEffect $input) => ($this->record)(
                fn($mutation) => $mutation
                    ->sections()
                    ->environment()
                    ->record($this->env->all()),
            )->map(static fn() => $input),
        );
    }
}

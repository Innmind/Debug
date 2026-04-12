<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Recorder\Exception,
    Record,
};
use Innmind\Router\{
    Component,
    Exception\NotFound,
};
use Innmind\Http\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
final class RecordException implements Recorder
{
    private function __construct(
        private Exception $exception,
        private Record $record = new Record\Nothing,
    ) {
    }

    /**
     * @return Component<SideEffect, Response>
     */
    public function __invoke(\Throwable $e): Component
    {
        if ($e instanceof NotFound) {
            /** @var Component<SideEffect, Response> */
            return Component::of(static fn(
                ServerRequest $request,
                SideEffect $_,
            ) => Attempt::error($e));
        }

        /** @var Attempt<Response> */
        $result = ($this->exception)($e)->flatMap(
            fn() => ($this->record)(
                static fn($mutation) => $mutation->fail('crashed'),
            ),
        )->flatMap(static fn() => Attempt::error($e));

        /** @var Component<SideEffect, Response> */
        return Component::of(static fn(
            ServerRequest $request,
            SideEffect $_,
        ) => $result);
    }

    /**
     * @internal
     */
    public static function of(Exception $record): self
    {
        return new self($record);
    }

    #[\Override]
    public function push(Record $record): void
    {
        $this->record = $record;
        $this->exception->push($record);
    }
}

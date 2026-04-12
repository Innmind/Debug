<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Profiler\Profiler;
use Innmind\Router\Component;
use Innmind\Http\ServerRequest;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
final class StartProfile
{
    /**
     * @internal
     * @psalm-pure
     *
     * @return Component<SideEffect, SideEffect>
     */
    public static function of(Profiler $profiler, Recorder $recorder): Component
    {
        return Component::of(static function(
            ServerRequest $request,
            SideEffect $input,
        ) use (
            $profiler,
            $recorder,
        ) {
            $inProfiler = \str_starts_with($request->url()->path()->toString(), '/_profiler');

            if ($inProfiler) {
                return Attempt::result($input);
            }

            return $profiler
                ->start(\sprintf(
                    '%s %s',
                    $request->method()->toString(),
                    $request->url()->path()->toString(),
                ))
                ->map(static fn($profile) => Record\Profile::of(
                    $profiler,
                    $profile,
                ))
                ->flatMap(
                    static fn($record) => $record(
                        static fn($mutation) => $mutation
                            ->sections()
                            ->http()
                            ->received(ServerRequest\Stringable::new()($request)),
                    )->map(static fn() => $record),
                )
                ->map($recorder->push(...))
                ->map(static fn(): mixed => $input);
        });
    }
}

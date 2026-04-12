<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Profiler\Profiler\Mutation;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
interface Record
{
    /**
     * @param callable(Mutation): Attempt<SideEffect> $mutation
     *
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function __invoke(callable $mutation): Attempt;
}

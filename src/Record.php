<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Profiler\Profiler\Mutation;

interface Record
{
    /**
     * @param callable(Mutation): void $mutation
     */
    public function __invoke(callable $mutation): void;
}

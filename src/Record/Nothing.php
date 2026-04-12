<?php
declare(strict_types = 1);

namespace Innmind\Debug\Record;

use Innmind\Debug\Record;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
final class Nothing implements Record
{
    #[\Override]
    public function __invoke(callable $mutation): Attempt
    {
        return Attempt::result(SideEffect::identity);
    }
}

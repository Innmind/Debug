<?php
declare(strict_types = 1);

namespace Innmind\Debug\Record;

use Innmind\Debug\Record;

/**
 * @internal
 */
final class Nothing implements Record
{
    public function __invoke(callable $mutation): void
    {
        // nothing to do
    }
}

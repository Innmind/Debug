<?php
declare(strict_types = 1);

namespace Innmind\Debug;

/**
 * @psalm-immutable
 */
enum IDE
{
    case sublimeText;
    case unknown;
}

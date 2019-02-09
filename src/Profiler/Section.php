<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler;

use Innmind\Debug\Profiler\Profile\Identity;

/**
 * `start` and `finish` both require the identity to enforce then to be called
 * only within a profiler so no other code can call them prematurely
 */
interface Section
{
    public function start(Identity $identity): void;
    public function finish(Identity $identity): void;
}

<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler;

use Innmind\Debug\Profiler\Profile\Identity;

interface Section
{
    public function start(Identity $identity): void;
    public function finish(Identity $identity): void;
}

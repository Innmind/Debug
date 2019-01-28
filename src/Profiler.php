<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Debug\Profiler\Profile\Identity;

interface Profiler
{
    public function start(string $name): Identity;
    public function fail(Identity $identity, string $exit): void;
    public function succeed(Identity $identity, string $exit): void;
}

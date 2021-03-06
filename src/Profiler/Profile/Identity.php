<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Profile;

final class Identity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}

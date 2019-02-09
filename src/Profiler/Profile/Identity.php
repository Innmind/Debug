<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Profile;

final class Identity
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

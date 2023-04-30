<?php
declare(strict_types = 1);

namespace Innmind\Debug\Record;

use Innmind\Debug\Record;
use Innmind\Profiler\{
    Profiler,
    Profile\Id,
};

/**
 * @internal
 */
final class Profile implements Record
{
    private Profiler $profiler;
    private Id $profile;

    private function __construct(Profiler $profiler, Id $profile)
    {
        $this->profiler = $profiler;
        $this->profile = $profile;
    }

    public function __invoke(callable $mutation): void
    {
        $this->profiler->mutate($this->profile, $mutation);
    }

    public static function of(Profiler $profiler, Id $profile): self
    {
        return new self($profiler, $profile);
    }
}

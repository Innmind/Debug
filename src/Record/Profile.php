<?php
declare(strict_types = 1);

namespace Innmind\Debug\Record;

use Innmind\Debug\Record;
use Innmind\Profiler\{
    Profiler,
    Profile\Id,
};
use Innmind\Immutable\Attempt;

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

    #[\Override]
    public function __invoke(callable $mutation): Attempt
    {
        return $this->profiler->mutate($this->profile, $mutation);
    }

    public static function of(Profiler $profiler, Id $profile): self
    {
        return new self($profiler, $profile);
    }
}

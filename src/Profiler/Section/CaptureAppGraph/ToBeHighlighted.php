<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Section\CaptureAppGraph;

use Innmind\Immutable\Set;

final class ToBeHighlighted
{
    /** @var Set<object> */
    private Set $set;

    public function __construct()
    {
        $this->set = Set::objects();
    }

    public function clear(): void
    {
        $this->set = $this->set->clear();
    }

    public function add(object $object): void
    {
        $this->set = ($this->set)($object);
    }

    /**
     * @return Set<object>
     */
    public function get(): Set
    {
        return $this->set;
    }
}

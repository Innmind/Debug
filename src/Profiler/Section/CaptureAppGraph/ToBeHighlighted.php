<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Section\CaptureAppGraph;

use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class ToBeHighlighted
{
    private $set;

    public function __construct()
    {
        $this->set = Set::of('object');
    }

    public function clear(): void
    {
        $this->set = $this->set->clear();
    }

    public function add(object $object): void
    {
        $this->set = $this->set->add($object);
    }

    /**
     * @return SetInterface<object>
     */
    public function get(): SetInterface
    {
        return $this->set;
    }
}

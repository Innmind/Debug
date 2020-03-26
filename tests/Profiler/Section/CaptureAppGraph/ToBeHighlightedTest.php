<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\Profiler\Section\CaptureAppGraph;

use Innmind\Debug\Profiler\Section\CaptureAppGraph\ToBeHighlighted;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class ToBeHighlightedTest extends TestCase
{
    public function testInterface()
    {
        $set = new ToBeHighlighted;

        $this->assertInstanceOf(Set::class, $set->get());
        $this->assertSame('object', (string) $set->get()->type());
        $this->assertCount(0, $set->get());
        $this->assertNull($set->add($object = new \stdClass));
        $this->assertCount(1, $set->get());
        $this->assertSame([$object], unwrap($set->get()));
        $this->assertNull($set->clear());
        $this->assertCount(0, $set->get());
    }
}

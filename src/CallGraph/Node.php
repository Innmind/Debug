<?php
declare(strict_types = 1);

namespace Innmind\Debug\CallGraph;

use Innmind\Debug\Exception\LogicException;
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
};
use Innmind\Immutable\Sequence;

final class Node
{
    private Clock $clock;
    private string $name;
    private ?PointInTime $startedAt = null;
    private ?PointInTime $endedAt = null;
    /** @var Sequence<self> */
    private Sequence $children;
    /** @var Sequence<self> */
    private Sequence $stack;

    private function __construct(Clock $clock, string $name)
    {
        $this->clock = $clock;
        $this->name = $name;
        /** @var Sequence<self> */
        $this->children = Sequence::of(self::class);
        /** @var Sequence<self> */
        $this->stack = Sequence::of(self::class);
    }

    public static function root(Clock $clock, string $name): self
    {
        return new self($clock, $name);
    }

    public function enter(string $name): void
    {
        $this->start();

        if ($this->ended()) {
            throw new LogicException;
        }

        $child = new self($this->clock, $name);
        $child->start();
        $this->add($child);
        $this->stack = ($this->stack)($child);
    }

    public function leave(): void
    {
        if ($this->ended()) {
            throw new LogicException;
        }

        if (!$this->stack->empty()) {
            $this->stack->last()->end();
            $this->stack = $this->stack->dropEnd(1);
        }
    }

    public function normalize(): array
    {
        $this->finish();

        /**
         * @psalm-suppress PossiblyNullArgument
         * @psalm-suppress PossiblyNullReference
         */
        return [
            'name' => $this->name,
            'value' => $this->endedAt->elapsedSince($this->startedAt)->milliseconds(),
            'children' => $this->children->reduce(
                [],
                static function(array $children, self $child): array {
                    $children[] = $child->normalize();

                    return $children;
                }
            ),
        ];
    }

    public function end(): void
    {
        if ($this->ended()) {
            return;
        }

        $this->endedAt = $this->clock->now();
    }

    private function start(): void
    {
        if ($this->started()) {
            return;
        }

        $this->startedAt = $this->clock->now();
    }

    private function started(): bool
    {
        return $this->startedAt instanceof PointInTime;
    }

    private function ended(): bool
    {
        return $this->endedAt instanceof PointInTime;
    }

    private function add(self $child): void
    {
        $parent = $this;

        if (!$this->stack->empty()) {
            $parent = $this->stack->last();
        }

        $parent->children = ($parent->children)($child);
    }

    /**
     * Close all open nodes if not already closed properly
     */
    private function finish(): void
    {
        $this->start();

        $this
            ->stack
            ->reverse()
            ->foreach(static function(self $node): void {
                $node->end();
            });

        $this->end();
    }
}

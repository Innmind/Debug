<?php
declare(strict_types = 1);

namespace Innmind\Debug\CallGraph;

use Innmind\Debug\Exception\LogicException;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
};
use Innmind\Immutable\Stream;

final class Node
{
    private TimeContinuumInterface $clock;
    private string $name;
    private ?PointInTimeInterface $startedAt = null;
    private ?PointInTimeInterface $endedAt = null;
    private Stream $children;
    private Stream $stack;

    private function __construct(TimeContinuumInterface $clock, string $name)
    {
        $this->clock = $clock;
        $this->name = $name;
        $this->children = Stream::of(self::class);
        $this->stack = Stream::of(self::class);
    }

    public static function root(TimeContinuumInterface $clock, string $name): self
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
        $this->stack = $this->stack->add($child);
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
        return $this->startedAt instanceof PointInTimeInterface;
    }

    private function ended(): bool
    {
        return $this->endedAt instanceof PointInTimeInterface;
    }

    private function add(self $child): void
    {
        $parent = $this;

        if (!$this->stack->empty()) {
            $parent = $this->stack->last();
        }

        $parent->children = $parent->children->add($child);
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

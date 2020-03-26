<?php
declare(strict_types = 1);

namespace Innmind\Debug\Closure;

use Innmind\Debug\{
    CallGraph,
    Profiler\Section\CaptureAppGraph\ToBeHighlighted,
};

/**
 * This class does 2 things as it is not possible to accomplish this goal by
 * using composition as the inner callable would be hidden behind a decorator
 * So it would fail to either capture the correct class name in the call graph
 * or it would instruct to highlight an object that is not really part of the
 * app graph
 */
final class CaptureCallGraph
{
    private $call;
    private CallGraph $graph;
    private ?ToBeHighlighted $toBeHighlighted;

    public function __construct(
        callable $call,
        CallGraph $graph,
        ToBeHighlighted $toBeHighlighted = null
    ) {
        $this->call = $call;
        $this->graph = $graph;
        $this->toBeHighlighted = $toBeHighlighted;
    }

    public function __invoke(...$arguments)
    {
        try {
            $this->graph->enter(
                \is_object($this->call) ? \get_class($this->call) : 'Closure'
            );

            if ($this->toBeHighlighted && \is_object($this->call)) {
                $this->toBeHighlighted->add($this->call);
            }

            return ($this->call)(...$arguments);
        } finally {
            $this->graph->leave();
        }
    }
}

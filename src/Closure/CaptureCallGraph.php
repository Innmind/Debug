<?php
declare(strict_types = 1);

namespace Innmind\Debug\Closure;

use Innmind\Debug\CallGraph;

final class CaptureCallGraph
{
    private $call;
    private $graph;

    public function __construct(callable $call, CallGraph $graph)
    {
        $this->call = $call;
        $this->graph = $graph;
    }

    public function __invoke(...$arguments)
    {
        try {
            $this->graph->enter(
                \is_object($this->call) ? \get_class($this->call) : 'Closure'
            );

            return ($this->call)(...$arguments);
        } finally {
            $this->graph->leave();
        }
    }
}

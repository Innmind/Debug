<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Debug\Profiler\Section\CaptureCallGraph;
use Innmind\TimeContinuum\Clock;

final class CallGraph
{
    private CaptureCallGraph $section;
    private Clock $clock;
    private ?CallGraph\Node $graph = null;

    public function __construct(CaptureCallGraph $section, Clock $clock)
    {
        $this->section = $section;
        $this->clock = $clock;
    }

    public function start(string $name): void
    {
        $this->graph = CallGraph\Node::root($this->clock, $name);
    }

    public function enter(string $name): void
    {
        if ($this->graph instanceof CallGraph\Node) {
            $this->graph->enter($name);
        }
    }

    public function leave(): void
    {
        if ($this->graph instanceof CallGraph\Node) {
            $this->graph->leave();
        }
    }

    public function end(): void
    {
        if ($this->graph instanceof CallGraph\Node) {
            $this->section->capture($this->graph);
            $this->graph = null;
        }
    }
}

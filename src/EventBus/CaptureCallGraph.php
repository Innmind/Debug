<?php
declare(strict_types = 1);

namespace Innmind\Debug\EventBus;

use Innmind\Debug\CallGraph;
use Innmind\EventBus\EventBus;

final class CaptureCallGraph implements EventBus
{
    private EventBus $dispatch;
    private CallGraph $graph;

    public function __construct(EventBus $dispatch, CallGraph $graph)
    {
        $this->dispatch = $dispatch;
        $this->graph = $graph;
    }

    public function __invoke(object $event): EventBus
    {
        try {
            $this->graph->enter(\get_class($event));

            ($this->dispatch)($event);

            return $this;
        } finally {
            $this->graph->leave();
        }
    }
}

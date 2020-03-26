<?php
declare(strict_types = 1);

namespace Innmind\Debug\CommandBus;

use Innmind\Debug\CallGraph;
use Innmind\CommandBus\CommandBus;

final class CaptureCallGraph implements CommandBus
{
    private CommandBus $handle;
    private CallGraph $graph;

    public function __construct(CommandBus $handle, CallGraph $graph)
    {
        $this->handle = $handle;
        $this->graph = $graph;
    }

    public function __invoke(object $command): void
    {
        try {
            $this->graph->enter(\get_class($command));

            ($this->handle)($command);
        } finally {
            $this->graph->leave();
        }
    }
}

<?php
declare(strict_types = 1);

namespace Innmind\Debug\CLI;

use Innmind\Debug\CallGraph;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};

final class StartCallGraph implements Command
{
    private Command $handle;
    private CallGraph $graph;
    private string $name;

    public function __construct(
        Command $handle,
        CallGraph $graph,
        string $name
    ) {
        $this->handle = $handle;
        $this->graph = $graph;
        $this->name = $name;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        try {
            $this->graph->start($this->name);

            ($this->handle)($env, $arguments, $options);
        } finally {
            $this->graph->end();
        }
    }

    public function __toString(): string
    {
        return (string) $this->handle;
    }
}

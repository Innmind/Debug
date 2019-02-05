<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\CallGraph;
use Innmind\OperatingSystem\{
    CurrentProcess as CurrentProcessInterface,
    CurrentProcess\ForkSide,
    CurrentProcess\Children,
    Exception\ForkFailed,
};
use Innmind\Server\Status\Server\Process\Pid;
use Innmind\TimeContinuum\PeriodInterface;

final class CurrentProcess implements CurrentProcessInterface
{
    private $process;
    private $graph;

    public function __construct(CurrentProcessInterface $process, CallGraph $graph)
    {
        $this->process = $process;
        $this->graph = $graph;
    }

    public function id(): Pid
    {
        return $this->process->id();
    }

    /**
     * {@inheritdoc}
     */
    public function fork(): ForkSide
    {
        $this->graph->enter('fork()');

        try {
            $side = $this->process->fork();
        } catch (ForkFailed $e) {
            $this->graph->leave();

            throw $e;
        }

        if ($side->parent()) {
            $this->graph->leave();
        }

        return $side;
    }

    public function children(): Children
    {
        return $this->process->children();
    }

    public function halt(PeriodInterface $period): void
    {
        $this->graph->enter('halt()');

        $this->process->halt($period);

        $this->graph->leave();
    }
}

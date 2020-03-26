<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\CallGraph;
use Innmind\OperatingSystem\{
    CurrentProcess as CurrentProcessInterface,
    CurrentProcess\ForkSide,
    CurrentProcess\Children,
    CurrentProcess\Signals,
    Exception\ForkFailed,
};
use Innmind\Server\Control\Server\Process\Pid;
use Innmind\Server\Status\Server\Memory\Bytes;
use Innmind\TimeContinuum\Period;

final class CurrentProcess implements CurrentProcessInterface
{
    private CurrentProcessInterface $process;
    private CallGraph $graph;

    public function __construct(CurrentProcessInterface $process, CallGraph $graph)
    {
        $this->process = $process;
        $this->graph = $graph;
    }

    public function id(): Pid
    {
        return $this->process->id();
    }

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

    public function signals(): Signals
    {
        return $this->process->signals();
    }

    public function halt(Period $period): void
    {
        $this->graph->enter('halt()');

        $this->process->halt($period);

        $this->graph->leave();
    }

    public function memory(): Bytes
    {
        return $this->process->memory();
    }
}

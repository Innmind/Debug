<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\CallGraph;

use Innmind\Debug\CallGraph;
use Innmind\OperatingSystem\{
    CurrentProcess as CurrentProcessInterface,
    CurrentProcess\ForkSide,
    CurrentProcess\Children,
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
        return $this->process->fork();
    }

    public function children(): Children
    {
        return $this->process-children();
    }

    public function halt(PeriodInterface $period): void
    {
        $this->graph->enter('halt()');

        $this->process->halt($period);

        $this->graph->leave();
    }
}

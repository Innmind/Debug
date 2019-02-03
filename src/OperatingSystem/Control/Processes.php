<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Control;

use Innmind\Debug\Profiler\{
    Section,
    Section\CaptureProcesses,
    Profile\Identity,
};
use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Process,
    Process\Pid,
    Command,
    Signal,
};
use Innmind\Immutable\Map;

final class Processes implements ProcessesInterface, Section
{
    private $processes;
    private $section;
    private $renderProcess;
    private $pairs;

    public function __construct(
        ProcessesInterface $processes,
        CaptureProcesses $section,
        RenderProcess $render
    ) {
        $this->processes = $processes;
        $this->section = $section;
        $this->render = $render;
        $this->pairs = Map::of(Command::class, Process::class);
    }

    public function execute(Command $command): Process
    {
        $process = $this->processes->execute($command);
        $this->pairs = $this->pairs->put($command, $process);

        return $process;
    }

    public function kill(Pid $pid, Signal $signal): ProcessesInterface
    {
        $this->processes->kill($pid, $signal);

        return $this;
    }

    public function start(Identity $identity): void
    {
        $this->section->start($identity);
        $this->pairs = $this->pairs->clear();
    }

    public function finish(Identity $identity): void
    {
        $this->pairs->foreach(function(Command $command, Process $process): void {
            $this->section->capture(
                ($this->render)($command, $process)
            );
        });
        $this->section->finish($identity);
    }
}

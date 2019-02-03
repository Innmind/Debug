<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Control\Processes;

use Innmind\Debug\{
    OperatingSystem\Control\RenderProcess,
    Profiler\Section\CaptureProcesses,
    Profiler\Section,
    Profiler\Profile\Identity,
};
use Innmind\Server\Control\Server\{
    Command,
    Process,
};
use Innmind\Immutable\Map;

final class State implements Section
{
    private $render;
    private $section;
    private $processes;

    public function __construct(
        RenderProcess $render,
        CaptureProcesses $section
    ) {
        $this->render = $render;
        $this->section = $section;
        $this->processes = Map::of(Command::class, Process::class);
    }

    public function register(Command $command, Process $process): void
    {
        $this->processes = $this->processes->put($command, $process);
    }

    public function start(Identity $identity): void
    {
        $this->section->start($identity);
        $this->processes = $this->processes->clear();
    }

    public function finish(Identity $identity): void
    {
        $this->processes->foreach(function(Command $command, Process $process): void {
            $this->section->capture(
                ($this->render)($command, $process)
            );
        });
        $this->section->finish($identity);
    }
}
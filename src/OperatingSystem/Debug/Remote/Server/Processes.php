<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug\Remote\Server;

use Innmind\Debug\OperatingSystem\Debug\Control\{
    Processes\State,
    RenderProcess\Remote,
};
use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Process,
    Process\Pid,
    Command,
    Signal,
};
use Innmind\Url\Url;

final class Processes implements ProcessesInterface
{
    private ProcessesInterface $processes;
    private Url $location;
    private Remote $render;
    private State $state;

    public function __construct(
        ProcessesInterface $processes,
        Url $location,
        Remote $render,
        State $state
    ) {
        $this->processes = $processes;
        $this->location = $location;
        $this->render = $render;
        $this->state = $state;
    }

    public function execute(Command $command): Process
    {
        $process = $this->processes->execute($command);
        $this->render->locate($command, $this->location);
        $this->state->register($command, $process);

        return $process;
    }

    public function kill(Pid $pid, Signal $signal): void
    {
        $this->processes->kill($pid, $signal);
    }
}

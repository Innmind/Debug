<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote\Server;

use Innmind\Debug\OperatingSystem\Control\{
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
use Innmind\Url\UrlInterface;

final class Processes implements ProcessesInterface
{
    private $processes;
    private $location;
    private $render;
    private $state;

    public function __construct(
        ProcessesInterface $processes,
        UrlInterface $location,
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

    public function kill(Pid $pid, Signal $signal): ProcessesInterface
    {
        $this->processes->kill($pid, $signal);

        return $this;
    }
}

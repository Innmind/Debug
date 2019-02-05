<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug\Control;

use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Process,
    Process\Pid,
    Command,
    Signal,
};

final class Processes implements ProcessesInterface
{
    private $processes;
    private $state;

    public function __construct(
        ProcessesInterface $processes,
        Processes\State $state
    ) {
        $this->processes = $processes;
        $this->state = $state;
    }

    public function execute(Command $command): Process
    {
        $process = $this->processes->execute($command);
        $this->state->register($command, $process);

        return $process;
    }

    public function kill(Pid $pid, Signal $signal): ProcessesInterface
    {
        $this->processes->kill($pid, $signal);

        return $this;
    }
}

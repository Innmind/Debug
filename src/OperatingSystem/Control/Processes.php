<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Control;

use Innmind\Debug\{
    Recorder\Beacon,
    OperatingSystem\Control\Process\Debug,
};
use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Command,
    Process,
    Process\Pid,
    Signal,
};
use Innmind\Immutable\Either;

final class Processes implements ProcessesInterface
{
    private ProcessesInterface $inner;
    private Beacon $beacon;

    private function __construct(ProcessesInterface $inner, Beacon $beacon)
    {
        $this->inner = $inner;
        $this->beacon = $beacon;
    }

    public static function of(ProcessesInterface $inner, Beacon $beacon): self
    {
        return new self($inner, $beacon);
    }

    public function execute(Command $command): Process
    {
        return Debug::of(
            $command,
            $this->inner->execute($command),
            $this->beacon->record(),
        );
    }

    public function kill(Pid $pid, Signal $signal): Either
    {
        return $this->inner->kill($pid, $signal);
    }
}

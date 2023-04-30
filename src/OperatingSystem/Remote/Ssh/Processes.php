<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote\Ssh;

use Innmind\Debug\{
    Recorder\Beacon,
    OperatingSystem\Remote\Ssh\Process\Debug,
};
use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Command,
    Process,
    Process\Pid,
    Signal,
};
use Innmind\Url\Url;
use Innmind\Immutable\Either;

final class Processes implements ProcessesInterface
{
    private ProcessesInterface $inner;
    private Beacon $beacon;
    private Url $server;

    private function __construct(
        ProcessesInterface $inner,
        Beacon $beacon,
        Url $server,
    ) {
        $this->inner = $inner;
        $this->beacon = $beacon;
        $this->server = $server;
    }

    public static function of(
        ProcessesInterface $inner,
        Beacon $beacon,
        Url $server,
    ): self {
        return new self($inner, $beacon, $server);
    }

    public function execute(Command $command): Process
    {
        return Debug::of(
            $this->server,
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

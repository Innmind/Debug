<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Control;

use Innmind\Server\Control\Server\{
    Command,
    Process,
};

interface RenderProcess
{
    public function __invoke(Command $command, Process $process): string;
}

<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug\Control\RenderProcess;

use Innmind\Debug\OperatingSystem\Debug\Control\RenderProcess;
use Innmind\Server\Control\Server\{
    Command,
    Process,
};

final class Local implements RenderProcess
{
    public function __invoke(Command $command, Process $process): string
    {
        if ($command->toBeRunInBackground()) {
            $status = 'background';
        } elseif ($process->isRunning()) {
            $status = 'still-running';
        } else {
            $status = $process->exitCode()->toString();
        }

        $directory = '';

        if ($command->hasWorkingDirectory()) {
            $directory = $command->workingDirectory()->toString().': ';
        }

        return \sprintf(
            "[%s] %s%s\n%s",
            $status,
            $directory,
            $command->toString(),
            $command->toBeRunInBackground() || $process->isRunning() ? '' : $process->output()->toString(),
        );
    }
}

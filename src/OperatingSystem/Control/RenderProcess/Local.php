<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Control\RenderProcess;

use Innmind\Debug\OperatingSystem\Control\RenderProcess;
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
            $status = (string) $process->exitCode();
        }

        $directory = '';

        if ($command->hasWorkingDirectory()) {
            $directory = $command->workingDirectory().': ';
        }

        return \sprintf(
            "[%s] %s%s\n%s",
            $status,
            $directory,
            $command,
            $command->toBeRunInBackground() || $process->isRunning() ? '' : $process->output()
        );
    }
}

<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Control\RenderProcess;

use Innmind\Debug\OperatingSystem\Control\RenderProcess;
use Innmind\Server\Control\Server\{
    Command,
    Process,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Map;

final class Remote implements RenderProcess
{
    private $render;
    private $commands;

    public function __construct(RenderProcess $render)
    {
        $this->render = $render;
        $this->commands = Map::of(Command::class, UrlInterface::class);
    }

    public function locate(Command $command, UrlInterface $location): void
    {
        $this->commands = $this->commands->put($command, $location);
    }

    public function __invoke(Command $command, Process $process): string
    {
        $string = ($this->render)($command, $process);

        if ($this->commands->contains($command)) {
            $string = \sprintf(
                "ssh: %s\n%s",
                $this->commands->get($command)->authority(),
                $string
            );
        }

        return $string;
    }
}

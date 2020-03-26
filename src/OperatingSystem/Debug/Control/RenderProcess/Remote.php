<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug\Control\RenderProcess;

use Innmind\Debug\OperatingSystem\Debug\Control\RenderProcess;
use Innmind\Server\Control\Server\{
    Command,
    Process,
};
use Innmind\Url\Url;
use Innmind\Immutable\Map;

final class Remote implements RenderProcess
{
    private RenderProcess $render;
    /** @var Map<Command, Url> */
    private Map $commands;

    public function __construct(RenderProcess $render)
    {
        $this->render = $render;
        /** @var Map<Command, Url> */
        $this->commands = Map::of(Command::class, Url::class);
    }

    public function locate(Command $command, Url $location): void
    {
        $this->commands = $this->commands->put($command, $location);
    }

    public function __invoke(Command $command, Process $process): string
    {
        $string = ($this->render)($command, $process);

        if ($this->commands->contains($command)) {
            $string = \sprintf(
                "ssh: %s\n%s",
                $this->commands->get($command)->authority()->toString(),
                $string
            );
        }

        return $string;
    }
}

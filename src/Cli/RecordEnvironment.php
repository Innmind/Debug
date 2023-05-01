<?php
declare(strict_types = 1);

namespace Innmind\Debug\Cli;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\Environment;
use Innmind\CLI\{
    Command,
    Console,
};

/**
 * @internal
 */
final class RecordEnvironment implements Command, Recorder
{
    private Record $record;
    private Command $inner;
    private Environment $env;

    public function __construct(
        Command $inner,
        Environment $env,
    ) {
        $this->record = new Record\Nothing;
        $this->inner = $inner;
        $this->env = $env;
    }

    public function __invoke(Console $console): Console
    {
        try {
            return ($this->inner)($console);
        } finally {
            ($this->record)(
                fn($mutation) => $mutation
                    ->sections()
                    ->environment()
                    ->record($this->env->all()),
            );
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function usage(): string
    {
        return $this->inner->usage();
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

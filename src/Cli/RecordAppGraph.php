<?php
declare(strict_types = 1);

namespace Innmind\Debug\Cli;

use Innmind\Debug\Recorder\AppGraph;
use Innmind\CLI\{
    Command,
    Console,
};

/**
 * @internal
 */
final class RecordAppGraph implements Command
{
    private Command $inner;
    private AppGraph $record;

    public function __construct(Command $inner, AppGraph $record)
    {
        $this->inner = $inner;
        $this->record = $record;
    }

    public function __invoke(Console $console): Console
    {
        try {
            return ($this->inner)($console);
        } finally {
            ($this->record)($this->inner);
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function usage(): string
    {
        return $this->inner->usage();
    }
}

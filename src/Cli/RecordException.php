<?php
declare(strict_types = 1);

namespace Innmind\Debug\Cli;

use Innmind\Debug\Recorder\Exception;
use Innmind\CLI\{
    Command,
    Console,
};

/**
 * @internal
 */
final class RecordException implements Command
{
    private Command $inner;
    private Exception $record;

    public function __construct(Command $inner, Exception $record)
    {
        $this->inner = $inner;
        $this->record = $record;
    }

    public function __invoke(Console $console): Console
    {
        try {
            return ($this->inner)($console);
        } catch (\Throwable $e) {
            ($this->record)($e);

            throw $e;
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

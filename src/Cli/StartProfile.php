<?php
declare(strict_types = 1);

namespace Innmind\Debug\Cli;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Profiler\Profiler;

/**
 * @internal
 */
final class StartProfile implements Command
{
    private Profiler $profiler;
    private Recorder $recorder;
    private Command $inner;

    public function __construct(
        Profiler $profiler,
        Recorder $recorder,
        Command $inner,
    ) {
        $this->profiler = $profiler;
        $this->recorder = $recorder;
        $this->inner = $inner;
    }

    public function __invoke(Console $console): Console
    {
        $profile = $this->profiler->start(\implode(' ', $console->environment()->arguments()->toList()));
        $this->recorder->push(Record\Profile::of($this->profiler, $profile));

        try {
            $console = ($this->inner)($console);
            $this->profiler->mutate(
                $profile,
                static fn($mutation) => $console->environment()->exitCode()->match(
                    static fn($exit) => match ($exit->successful()) {
                        true => $mutation->succeed('0'),
                        false => $mutation->fail((string) $exit->toInt()),
                    },
                    static fn() => $mutation->succeed('0'),
                ),
            );

            return $console;
        } finally {
            $this->recorder->push(new Record\Nothing);
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

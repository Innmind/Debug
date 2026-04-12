<?php
declare(strict_types = 1);

namespace Innmind\Debug\Cli;

use Innmind\Debug\{
    Recorder,
    Record as _Record,
};
use Innmind\Profiler\Profiler;
use Innmind\CLI\{
    Command,
    Command\Usage,
    Console,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Record implements Command
{
    private function __construct(
        private Command $inner,
        private Profiler $profiler,
        private Recorder $recorder,
        private Recorder\Exception $exception,
    ) {
    }

    #[\Override]
    public function __invoke(Console $console): Attempt
    {
        $name = \implode(' ', $console->environment()->arguments()->toList());

        return $this
            ->profiler
            ->start($name)
            ->map(fn($profile) => _Record\Profile::of(
                $this->profiler,
                $profile,
            ))
            ->flatMap(
                static fn($record) => $record(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->environment()
                        ->record($console->variables()),
                )->map(static fn() => $record),
            )
            ->flatMap(function($record) use ($console) {
                $this->recorder->push($record);

                return ($this->inner)($console)->eitherWay(
                    fn($console) => $this->result($console, $record),
                    fn($e) => $this->error($e, $record),
                );
            })
            ->map(function($console) {
                $this->recorder->push(new _Record\Nothing);

                return $console;
            })
            ->mapError(function($e) {
                $this->recorder->push(new _Record\Nothing);

                return $e;
            });
    }

    public static function of(
        Command $inner,
        Profiler $profiler,
        Recorder $recorder,
        Recorder\Exception $exception,
    ): self {
        return new self($inner, $profiler, $recorder, $exception);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function usage(): Usage
    {
        return $this->inner->usage();
    }

    /**
     * @return Attempt<Console>
     */
    private function result(
        Console $console,
        _Record $record,
    ): Attempt {
        return $record(
            static fn($mutation) => $console->environment()->exitCode()->match(
                static fn($exit) => match ($exit->successful()) {
                    true => $mutation->succeed('0'),
                    false => $mutation->fail((string) $exit->toInt()),
                },
                static fn() => $mutation->succeed('0'),
            ),
        )->map(static fn() => $console);
    }

    /**
     * @return Attempt<Console>
     */
    private function error(
        \Throwable $e,
        _Record $record,
    ): Attempt {
        /** @var Attempt<Console> */
        return ($this->exception)($e)
            ->flatMap(static fn() => $record(
                static fn($mutation) => $mutation->fail('crashed'),
            ))
            ->flatMap(static fn() => Attempt::error($e));
    }
}

<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Control\Process;

use Innmind\Debug\Record;
use Innmind\Server\Control\Server\{
    Command,
    Process,
    Process\Output,
    Process\Failed,
    Process\Signaled,
    Process\TimedOut,
};
use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Maybe,
    Either,
    Sequence,
    Str,
};

final class Debug implements Process
{
    private Command $command;
    private Process $inner;
    private Record $record;

    private function __construct(
        Command $command,
        Process $inner,
        Record $record,
    ) {
        $this->command = $command;
        $this->inner = $inner;
        $this->record = $record;
    }

    public static function of(
        Command $command,
        Process $process,
        Record $record,
    ): self {
        return new self($command, $process, $record);
    }

    public function pid(): Maybe
    {
        return $this->inner->pid();
    }

    public function output(): Output
    {
        return $this->inner->output();
    }

    public function wait(): Either
    {
        $command = $this->command->toString();
        $command = $this->command->workingDirectory()->match(
            static fn($path) => $path->toString().': '.$command,
            static fn() => $command,
        );

        // Either<TimedOut|Failed|Signaled, Success>
        return $this
            ->inner
            ->wait()
            ->map(function($success) use ($command) {
                $content = Sequence::lazyStartingWith(Str::of('[0] '.$command));

                if (!$this->command->outputToBeStreamed()) {
                    $content = $content
                        ->add(Str::of("\n"))
                        ->append(
                            $success
                                ->output()
                                ->chunks()
                                ->map(static fn($pair) => $pair[0]),
                        );
                }

                ($this->record)(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->processes()
                        ->record(Content\Chunks::of($content)),
                );

                return $success;
            })
            ->leftMap(function($error) use ($command) {
                $status = match (true) {
                    $error instanceof Failed => $error->exitCode()->toString(),
                    $error instanceof TimedOut => 'timed-out',
                    $error instanceof Signaled => 'signaled',
                };
                $content = Sequence::lazyStartingWith(Str::of(\sprintf(
                    '[%s] %s',
                    $status,
                    $command,
                )));

                if ($error instanceof Failed && !$this->command->outputToBeStreamed()) {
                    $content = $content
                        ->add(Str::of("\n"))
                        ->append(
                            $error
                                ->output()
                                ->chunks()
                                ->map(static fn($pair) => $pair[0]),
                        );
                }

                ($this->record)(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->processes()
                        ->record(Content\Chunks::of($content)),
                );

                return $error;
            });
    }
}

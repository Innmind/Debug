<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote\Ssh\Process;

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
use Innmind\Url\Url;
use Innmind\Immutable\{
    Maybe,
    Either,
    Sequence,
    Str,
};

/**
 * @internal
 */
final class Debug implements Process
{
    private Url $server;
    private Command $command;
    private Process $inner;
    private Record $record;

    private function __construct(
        Url $server,
        Command $command,
        Process $inner,
        Record $record,
    ) {
        $this->server = $server;
        $this->command = $command;
        $this->inner = $inner;
        $this->record = $record;
    }

    public static function of(
        Url $server,
        Command $command,
        Process $process,
        Record $record,
    ): self {
        return new self($server, $command, $process, $record);
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
        $ssh = \sprintf(
            "ssh: %s\n",
            $this->server->authority()->toString(),
        );
        $command = $this->command->toString();
        $command = $this->command->workingDirectory()->match(
            static fn($path) => $path->toString().': '.$command,
            static fn() => $command,
        );

        // Either<TimedOut|Failed|Signaled, Success>
        return $this
            ->inner
            ->wait()
            ->map(function($success) use ($ssh, $command) {
                $content = Sequence::lazyStartingWith($ssh, '[0] '.$command)
                    ->map(Str::of(...));

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
                        ->remote()
                        ->processes()
                        ->record(Content\Chunks::of($content)),
                );

                return $success;
            })
            ->leftMap(function($error) use ($ssh, $command) {
                $status = match (true) {
                    $error instanceof Failed => $error->exitCode()->toString(),
                    $error instanceof TimedOut => 'timed-out',
                    $error instanceof Signaled => 'signaled',
                };
                $content = Sequence::lazyStartingWith(
                    $ssh,
                    \sprintf(
                        '[%s] %s',
                        $status,
                        $command,
                    ),
                )->map(Str::of(...));

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
                        ->remote()
                        ->processes()
                        ->record(Content\Chunks::of($content)),
                );

                return $error;
            });
    }
}

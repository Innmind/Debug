<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem;

use Innmind\Debug\Recorder\Beacon;
use Innmind\OperatingSystem\Config;
use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Server\{
    Control,
    Control\Server\Command,
};
use Innmind\HttpTransport\{
    Transport,
    Information,
    Redirection,
    ClientError,
    ServerError,
    MalformedResponse,
    ConnectionFailed,
    Failure,
};
use Innmind\Http\{
    Request,
    Response,
};
use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Sequence,
    Either,
    Str,
};

/**
 * @psalm-suppress InternalMethod
 */
final class Debug
{
    private function __construct(
        private Beacon $beacon,
    ) {
    }

    public function __invoke(Config $config): Config
    {
        return $config
            ->mapServerControl(fn($server) => Control\Server::via(function($command) use ($server) {
                // todo find a way to record the exit and output
                $record = $this->beacon->record();

                if ($command instanceof Command\OverSsh) {
                    $port = $command->port();
                    $port = match ($port) {
                        null => '',
                        default => $port->format(),
                    };
                    $ssh = \sprintf(
                        'ssh: %s@%s%s',
                        $command->user()->toString(),
                        $command->host()->toString(),
                        $port,
                    );
                    $normalized = $command->normalize();
                    $inner = $command->command();
                    $name = $inner->toString();

                    if ($inner instanceof Command) {
                        $name = $inner->workingDirectory()->match(
                            static fn($path) => $path->toString().': '.$name,
                            static fn() => $name,
                        );
                    }

                    return $record(
                        static fn($mutation) => $mutation
                            ->sections()
                            ->remote()
                            ->processes()
                            ->record(Content::ofChunks(
                                Sequence::of($ssh, $name)
                                    ->map(Str::of(...))
                                    ->map(static fn($chunk) => $chunk->append("\n")),
                            )),
                    )->flatMap(static fn() => $server->processes()->execute($normalized));
                }

                // to not record this package usage of the OS
                if ($command->environment()->contains('X_INNMIND_DEBUG')) {
                    return $server
                        ->processes()
                        ->execute($command);
                }

                $name = $command->toString();
                $name = $command->workingDirectory()->match(
                    static fn($path) => $path->toString().': '.$name,
                    static fn() => $name,
                );

                return $record(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->processes()
                        ->record(Content::ofString($name)),
                )->flatMap(static fn() => $server->processes()->execute($command));
            }))
            ->mapHttpTransport(fn($transport) => Transport::via(function($request) use ($transport) {
                $record = $this->beacon->record();
                $toString = Response\Stringable::new();

                return $record(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->remote()
                        ->http()
                        ->sent(Request\Stringable::new()($request)),
                )
                    ->either()
                    ->leftMap(static fn($e) => new Failure(
                        $request,
                        $e->getMessage(),
                    ))
                    ->flatMap(
                        static fn() => $transport($request)
                            ->flatMap(
                                static fn($success) => $record(
                                    static fn($mutation) => $mutation
                                        ->sections()
                                        ->remote()
                                        ->http()
                                        ->got($toString($success->response())),
                                )
                                    ->either()
                                    ->leftMap(static fn($e) => new Failure(
                                        $request,
                                        $e->getMessage(),
                                    ))
                                    ->map(static fn() => $success),
                            )
                            ->otherwise(static function($error) use ($request, $record, $toString) {
                                $got = match (true) {
                                    $error instanceof Information => $toString($error->response()),
                                    $error instanceof Redirection => $toString($error->response()),
                                    $error instanceof ClientError => $toString($error->response()),
                                    $error instanceof ServerError => $toString($error->response()),
                                    $error instanceof MalformedResponse => Content::ofString('malformed response'),
                                    $error instanceof ConnectionFailed => Content::ofString($error->reason()),
                                    $error instanceof Failure => Content::ofString($error->reason()),
                                };

                                return $record(
                                    static fn($mutation) => $mutation
                                        ->sections()
                                        ->remote()
                                        ->http()
                                        ->got($got),
                                )
                                    ->either()
                                    ->leftMap(static fn($e) => new Failure(
                                        $request,
                                        $e->getMessage(),
                                    ))
                                    ->flatMap(static fn() => Either::left($error));
                            }),
                    );
            }))
            ->mapSQLConnection(fn($connection) => Connection::intercept(
                $connection,
                function($run, $driver, $query) {
                    $normalized = $query;

                    if ($normalized instanceof Query\Builder) {
                        $normalized = $normalized->normalize($driver);
                    }

                    $parameters = $normalized
                        ->parameters()
                        ->map(static fn($parameter) => \sprintf(
                            "%s: %s\n",
                            $parameter->name()->match(
                                static fn($name) => $name,
                                static fn() => '?',
                            ),
                            (string) $parameter->value(),
                        ))
                        ->map(Str::of(...));
                    // the sql connection throws when something fails
                    $_ = $this
                        ->beacon
                        ->record()(
                            static fn($mutation) => $mutation
                                ->sections()
                                ->remote()
                                ->sql()
                                ->record(Content::ofChunks(
                                    Sequence::of($normalized->sql())
                                        ->add("\n")
                                        ->map(Str::of(...))
                                        ->append($parameters),
                                )),
                        )
                        ->unwrap();

                    return $run($query);
                },
            ));
    }

    public static function of(Beacon $beacon): self
    {
        return new self($beacon);
    }
}

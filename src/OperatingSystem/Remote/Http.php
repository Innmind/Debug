<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote;

use Innmind\Debug\Recorder\Beacon;
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
use Innmind\Immutable\Either;

/**
 * @internal
 */
final class Http implements Transport
{
    private Transport $inner;
    private Beacon $beacon;

    private function __construct(Transport $inner, Beacon $beacon)
    {
        $this->inner = $inner;
        $this->beacon = $beacon;
    }

    public function __invoke(Request $request): Either
    {
        $record = $this->beacon->record();
        $record(
            static fn($mutation) => $mutation
                ->sections()
                ->remote()
                ->http()
                ->sent(Request\Stringable::new()($request)),
        );
        $toString = Response\Stringable::new();

        return ($this->inner)($request)
            ->map(static function($success) use ($record, $toString) {
                $record(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->remote()
                        ->http()
                        ->got($toString($success->response())),
                );

                return $success;
            })
            ->leftMap(static function($error) use ($record, $toString) {
                $got = match (true) {
                    $error instanceof Information => $toString($error->response()),
                    $error instanceof Redirection => $toString($error->response()),
                    $error instanceof ClientError => $toString($error->response()),
                    $error instanceof ServerError => $toString($error->response()),
                    $error instanceof MalformedResponse => Content::ofString('malformed response'),
                    $error instanceof ConnectionFailed => Content::ofString($error->reason()),
                    $error instanceof Failure => Content::ofString($error->reason()),
                };
                $record(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->remote()
                        ->http()
                        ->got($got),
                );

                return $error;
            });
    }

    public static function of(Transport $inner, Beacon $beacon): self
    {
        return new self($inner, $beacon);
    }
}

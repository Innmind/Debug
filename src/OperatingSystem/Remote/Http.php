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
use Innmind\Http\Message\{
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
                ->sent(Request\Stringable::of($request)->asContent()),
        );

        return ($this->inner)($request)
            ->map(static function($success) use ($record) {
                $record(
                    static fn($mutation) => $mutation
                        ->sections()
                        ->remote()
                        ->http()
                        ->got(Response\Stringable::of($success->response())->asContent()),
                );

                return $success;
            })
            ->leftMap(static function($error) use ($record) {
                $got = match (true) {
                    $error instanceof Information => Response\Stringable::of($error->response())->asContent(),
                    $error instanceof Redirection => Response\Stringable::of($error->response())->asContent(),
                    $error instanceof ClientError => Response\Stringable::of($error->response())->asContent(),
                    $error instanceof ServerError => Response\Stringable::of($error->response())->asContent(),
                    $error instanceof MalformedResponse => Content\Lines::ofContent('malformed response'),
                    $error instanceof ConnectionFailed => Content\Lines::ofContent($error->reason()),
                    $error instanceof Failure => Content\Lines::ofContent($error->reason()),
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

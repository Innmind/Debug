<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote;

use Innmind\Debug\Recorder\Beacon;
use Innmind\HttpTransport\{
    Transport,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Filesystem\File\Content;
use Innmind\Immutable\Either;

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
                ->sent(Content\Lines::ofContent(
                    (new Request\Stringable($request))->toString(),
                )),
        );

        return ($this->inner)($request)
            ->map(static fn($success) => $success) // todo record
            ->leftMap(static fn($error) => $error); // todo record
    }

    public static function of(Transport $inner, Beacon $beacon): self
    {
        return new self($inner, $beacon);
    }
}

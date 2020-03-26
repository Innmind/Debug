<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Debug\Remote;

use Innmind\Debug\Profiler\Section\Remote\CaptureHttp;
use Innmind\HttpTransport\Transport;
use Innmind\Http\Message\{
    Request,
    Response,
};

final class Http implements Transport
{
    private Transport $fulfill;
    private CaptureHttp $section;

    public function __construct(Transport $fulfill, CaptureHttp $section)
    {
        $this->fulfill = $fulfill;
        $this->section = $section;
    }

    public function __invoke(Request $request): Response
    {
        $response = ($this->fulfill)($request);

        $this->section->capture(
            (new Request\Stringable($request))->toString(),
            (new Response\Stringable($response))->toString(),
        );

        return $response;
    }
}

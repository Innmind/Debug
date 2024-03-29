<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Framework\Http\RequestHandler;
use Innmind\Profiler\Profiler;
use Innmind\Http\{
    ServerRequest,
    Response,
};

/**
 * @internal
 */
final class StartProfile implements RequestHandler
{
    private Profiler $profiler;
    private Recorder $recorder;
    private RequestHandler $inner;

    public function __construct(
        Profiler $profiler,
        Recorder $recorder,
        RequestHandler $inner,
    ) {
        $this->profiler = $profiler;
        $this->recorder = $recorder;
        $this->inner = $inner;
    }

    public function __invoke(ServerRequest $request): Response
    {
        $inProfiler = \str_starts_with($request->url()->path()->toString(), '/_profiler');

        return match ($inProfiler) {
            true => $this->dontProfile($request),
            false => $this->profile($request),
        };
    }

    private function dontProfile(ServerRequest $request): Response
    {
        return ($this->inner)($request);
    }

    private function profile(ServerRequest $request): Response
    {
        $profile = $this->profiler->start(\sprintf(
            '%s %s',
            $request->method()->toString(),
            $request->url()->path()->toString(),
        ));
        $this->recorder->push(Record\Profile::of($this->profiler, $profile));

        try {
            $response = ($this->inner)($request);
            $this->profiler->mutate(
                $profile,
                static fn($mutation) => match ($response->statusCode()->successful()) {
                    true => $mutation->succeed($response->statusCode()->toString()),
                    false => $mutation->fail($response->statusCode()->toString()),
                },
            );

            return $response;
        } catch (\Throwable $e) {
            $this->profiler->mutate(
                $profile,
                static fn($mutation) => $mutation->fail('crashed'),
            );

            throw $e;
        } finally {
            $this->recorder->push(new Record\Nothing);
        }
    }
}

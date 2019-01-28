<?php
declare(strict_types = 1);

namespace Innmind\Debug\HttpFramework;

use Innmind\Debug\{
    Profiler,
    Profiler\Profile\Identity,
};
use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\Str;

final class StartProfile implements RequestHandler
{
    private $handle;
    private $profiler;

    public function __construct(
        RequestHandler $handle,
        Profiler $profiler
    ) {
        $this->handle = $handle;
        $this->profiler = $profiler;
    }

    public function __invoke(ServerRequest $request): Response
    {
        $raw = new ServerRequest\Stringable($request);

        $profile = $this->profiler->start(
            (string) Str::of((string) $raw)->split("\n")->first()
        );

        try {
            $response = ($this->handle)($request);

            $this->end($profile, $response);
        } catch (\Throwable $e) {
            $this->profiler->fail($profile, '500');

            throw $e;
        }

        return $response;
    }

    private function end(Identity $profile, Response $response): void
    {
        $code = $response->statusCode();

        if ($code->value() >= 400) {
            $this->profiler->fail($profile, (string) $code);
        } else {
            $this->profiler->succeed($profile, (string) $code);
        }
    }
}

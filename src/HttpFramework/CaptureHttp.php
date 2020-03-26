<?php
declare(strict_types = 1);

namespace Innmind\Debug\HttpFramework;

use Innmind\Debug\Profiler\Section\CaptureHttp as Section;
use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

final class CaptureHttp implements RequestHandler
{
    private RequestHandler $handle;
    private Section $section;

    public function __construct(
        RequestHandler $handle,
        Section $section
    ) {
        $this->handle = $handle;
        $this->section = $section;
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->section->received((new ServerRequest\Stringable($request))->toString());

        $response = ($this->handle)($request);

        $this->section->respondedWith((new Response\Stringable($response))->toString());

        return $response;
    }
}

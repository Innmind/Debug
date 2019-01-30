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
    private $handle;
    private $section;

    public function __construct(
        RequestHandler $handle,
        Section $section
    ) {
        $this->handle = $handle;
        $this->section = $section;
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->section->received((string) new ServerRequest\Stringable($request));

        $response = ($this->handle)($request);

        $this->section->respondedWith((string) new Response\Stringable($response));

        return $response;
    }
}

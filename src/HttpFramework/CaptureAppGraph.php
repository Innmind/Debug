<?php
declare(strict_types = 1);

namespace Innmind\Debug\HttpFramework;

use Innmind\Debug\Profiler\Section\CaptureAppGraph as Section;
use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

final class CaptureAppGraph implements RequestHandler
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
        try {
            return ($this->handle)($request);
        } finally {
            $this->section->capture($this->handle);
        }
    }
}

<?php
declare(strict_types = 1);

namespace Innmind\Debug\HttpFramework;

use Innmind\Debug\CallGraph;
use Innmind\HttpFramework\Controller;
use Innmind\Router\Route;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\MapInterface;

final class CaptureController implements Controller
{
    private Controller $handle;
    private CallGraph $graph;

    public function __construct(Controller $handle, CallGraph $graph)
    {
        $this->handle = $handle;
        $this->graph = $graph;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        ServerRequest $request,
        Route $route,
        MapInterface $arguments
    ): Response {
        try {
            $this->graph->enter(\sprintf(
                '%s(%s)',
                \get_class($this->handle),
                $route->name()
            ));

            return ($this->handle)($request, $route, $arguments);
        } finally {
            $this->graph->leave();
        }
    }
}

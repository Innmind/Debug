<?php
declare(strict_types = 1);

namespace Innmind\Debug\HttpFramework;

use Innmind\Debug\CallGraph;
use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

final class StartCallGraph implements RequestHandler
{
    private $handle;
    private $graph;
    private $name;

    public function __construct(RequestHandler $handle, CallGraph $graph, string $name)
    {
        $this->handle = $handle;
        $this->graph = $graph;
        $this->name = $name;
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            $this->graph->start($this->name);

            return ($this->handle)($request);
        } finally {
            $this->graph->end();
        }
    }
}

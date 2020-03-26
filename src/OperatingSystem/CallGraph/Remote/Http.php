<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\CallGraph\Remote;

use Innmind\Debug\CallGraph;
use Innmind\HttpTransport\Transport;
use Innmind\Http\Message\{
    Request,
    Response,
};

final class Http implements Transport
{
    private Transport $fulfill;
    private CallGraph $graph;

    public function __construct(Transport $fulfill, CallGraph $graph)
    {
        $this->fulfill = $fulfill;
        $this->graph = $graph;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->graph->enter(\sprintf(
                'http(%s)',
                $request->url()->toString(),
            ));

            return ($this->fulfill)($request);
        } finally {
            $this->graph->leave();
        }
    }
}

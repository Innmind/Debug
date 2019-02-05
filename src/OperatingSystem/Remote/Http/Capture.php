<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote\Http;

use Innmind\Debug\CallGraph;
use Innmind\HttpTransport\Transport;
use Innmind\Http\Message\{
    Request,
    Response,
};

final class Capture implements Transport
{
    private $fulfill;
    private $graph;

    public function __construct(
        Transport $fulfill,
        CallGraph $graph
    ) {
        $this->fulfill = $fulfill;
        $this->graph = $graph;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->graph->enter(\sprintf(
                'http(%s)',
                $request->url()
            ));

            return ($this->fulfill)($request);
        } finally {
            $this->graph->leave();
        }
    }
}

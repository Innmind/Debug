<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Framework\{
    Application,
    Middleware,
};

/**
 * @psalm-suppress ArgumentTypeCoercion
 */
final class Kernel implements Middleware
{
    public function __invoke(Application $app): Application
    {
        return $app
            ->mapRequestHandler(static function($handler, $get) {
                $recordAppGraph = new Http\RecordAppGraph(
                    new Record\Nothing,
                    $handler,
                );
                $recordException = new Http\RecordException(
                    new Record\Nothing,
                    $recordAppGraph,
                );

                return new Http\StartProfile(
                    $get('innmind/profiler'),
                    Recorder\All::of(
                        $recordAppGraph,
                        $recordException,
                    ),
                    $recordException,
                );
            });
    }
}

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
            ->mapRequestHandler(static fn($handler, $get) => new Http\StartProfile(
                $get('innmind/profiler'),
                $inner = new Http\RecordException(
                    new Record\Nothing,
                    $handler,
                ),
                $inner,
            ));
    }
}

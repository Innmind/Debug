<?php
declare(strict_types = 1);

namespace Innmind\Debug\Http;

use Innmind\Debug\{
    Recorder,
    Record,
};
use Innmind\Router\Component;
use Innmind\Http\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class FreeProfile
{
    /**
     * @internal
     * @psalm-pure
     *
     * @return Component<Response, Response>
     */
    public static function of(Recorder $recorder): Component
    {
        return Component::of(static function(
            ServerRequest $request,
            Response $input,
        ) use (
            $recorder,
        ) {
            $recorder->push(new Record\Nothing);

            return Attempt::result($input);
        });
    }
}

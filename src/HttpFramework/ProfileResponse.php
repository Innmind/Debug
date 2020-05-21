<?php
declare(strict_types = 1);

namespace Innmind\Debug\HttpFramework;

use Innmind\Debug\Profiler\Profile\Identity;
use Innmind\Http\{
    Message\Response,
    Message\StatusCode,
    Message\ReasonPhrase,
    Headers,
    ProtocolVersion,
    Header\Header,
    Header\Value\Value,
};
use Innmind\Stream\Readable;

final class ProfileResponse implements Response
{
    private Response $response;
    private Identity $profile;

    public function __construct(Response $response, Identity $profile)
    {
        $this->response = $response;
        $this->profile = $profile;
    }

    public function protocolVersion(): ProtocolVersion
    {
        return $this->response->protocolVersion();
    }

    public function headers(): Headers
    {
        return $this
            ->response
            ->headers()
            ->add(new Header(
                'X-Profile',
                new Value($this->profile->toString()),
            ));
    }

    public function body(): Readable
    {
        return $this->response->body();
    }

    public function statusCode(): StatusCode
    {
        return $this->response->statusCode();
    }

    public function reasonPhrase(): ReasonPhrase
    {
        return $this->response->reasonPhrase();
    }
}

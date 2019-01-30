<?php
declare(strict_types = 1);

namespace Innmind\Debug\Profiler\Section;

use Innmind\Debug\Profiler\{
    Section,
    Profile\Identity,
};
use Innmind\Rest\Client\{
    Server,
    HttpResource,
    HttpResource\Property,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\StackTrace\{
    StackTrace,
    Render,
};

final class CaptureException implements Section
{
    private $server;
    private $processes;
    private $render;
    private $profile;

    public function __construct(
        Server $server,
        Processes $processes,
        Render $render
    ) {
        $this->server = $server;
        $this->processes = $processes;
        $this->render = $render;
    }

    public function start(Identity $identity): void
    {
        $this->profile = $identity;
    }

    public function capture(\Throwable $e): void
    {
        if (\is_null($this->profile)) {
            return;
        }

        $this->server->create(HttpResource::of(
            'api.section.exception',
            new Property('profile', (string) $this->profile),
            new Property(
                'graph',
                (string) $this
                    ->processes
                    ->execute(
                        Command::foreground('dot')
                            ->withShortOption('Tsvg')
                            ->withInput(
                                ($this->render)(new StackTrace($e))
                            )
                    )
                    ->wait()
                    ->output()
            )
        ));
    }

    public function finish(Identity $identity): void
    {
        $this->profile = null;
    }
}

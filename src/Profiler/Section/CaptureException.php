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
    private Server $server;
    private Processes $processes;
    private Render $render;
    private ?Identity $profile = null;
    private ?\Throwable $exception = null;

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
        $this->exception = null;
    }

    public function capture(\Throwable $e): void
    {
        $this->exception = $e;
    }

    public function finish(Identity $identity): void
    {
        if (\is_null($this->profile) || \is_null($this->exception)) {
            return;
        }

        $process = $this->processes->execute(
            Command::foreground('dot')
                ->withShortOption('Tsvg')
                ->withInput(
                    ($this->render)(new StackTrace($this->exception)),
                ),
        );
        $process->wait();

        $this->server->create(HttpResource::of(
            'api.section.exception',
            new Property('profile', $this->profile->toString()),
            new Property(
                'graph',
                $process->output()->toString(),
            ),
        ));
        $this->profile = null;
        $this->exception = null;
    }
}

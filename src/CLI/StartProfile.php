<?php
declare(strict_types = 1);

namespace Innmind\Debug\CLI;

use Innmind\Debug\{
    Profiler,
    Profiler\Profile\Identity,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};

final class StartProfile implements Command
{
    private $handle;
    private $profiler;

    public function __construct(
        Command $handle,
        Profiler $profiler
    ) {
        $this->handle = $handle;
        $this->profiler = $profiler;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $profile = $this->profiler->start(
            (string) $env->arguments()->join(' ')
        );

        try {
            ($this->handle)($env, $arguments, $options);

            $this->end($profile, $env);
        } catch (\Throwable $e) {
            $this->profiler->fail($profile, '1');

            throw $e;
        }
    }

    public function __toString(): string
    {
        return (string) $this->handle;
    }

    private function end(Identity $profile, Environment $env): void
    {
        $code = $env->exitCode();

        if ($code->successful()) {
            $this->profiler->succeed($profile, '0');
        } else {
            $this->profiler->fail($profile, (string) $code->toInt());
        }
    }
}

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
use function Innmind\Immutable\join;

final class StartProfile implements Command
{
    private Command $handle;
    private Profiler $profiler;

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
            join(' ', $env->arguments())->toString(),
        );

        try {
            ($this->handle)($env, $arguments, $options);

            $this->end($profile, $env);
        } catch (\Throwable $e) {
            $this->profiler->fail($profile, '1');

            throw $e;
        }
    }

    public function toString(): string
    {
        return $this->handle->toString();
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

<?php
declare(strict_types = 1);

namespace Innmind\Debug\CLI;

use Innmind\Debug\Profiler\Section\CaptureException as Section;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};

final class CaptureException implements Command
{
    private Command $handle;
    private Section $section;

    public function __construct(
        Command $handle,
        Section $section
    ) {
        $this->handle = $handle;
        $this->section = $section;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        try {
            ($this->handle)($env, $arguments, $options);
        } catch (\Throwable $e) {
            $this->section->capture($e);

            throw $e;
        }
    }

    public function toString(): string
    {
        return $this->handle->toString();
    }
}

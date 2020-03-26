<?php
declare(strict_types = 1);

namespace Innmind\Debug\CLI;

use Innmind\Debug\Profiler\Section\CaptureAppGraph as Section;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};

final class CaptureAppGraph implements Command
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
        } finally {
            $this->section->capture($this->handle);
        }
    }

    public function __toString(): string
    {
        return (string) $this->handle;
    }
}

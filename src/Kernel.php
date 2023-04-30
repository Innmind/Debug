<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Framework\{
    Application,
    Middleware,
};

final class Kernel implements Middleware
{
    private IDE $ide;

    private function __construct(IDE $ide)
    {
        $this->ide = $ide;
    }

    public function __invoke(Application $app): Application
    {
        return $app
            ->map($this->operatingSystem())
            ->map($this->app());
    }

    public static function inApp(): self
    {
        return new self(IDE::unknown);
    }

    public function operatingSystem(): Middleware
    {
        return new Kernel\OperatingSystem;
    }

    public function app(): Middleware
    {
        return new Kernel\App($this->ide);
    }

    /**
     * @psalm-mutation-free
     */
    public function usingIDE(IDE $ide): self
    {
        return new self($ide);
    }
}

<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Framework\{
    Application,
    Middleware,
};
use Innmind\StackTrace\FormatPath;
use Innmind\Url\Url;

final class Kernel implements Middleware
{
    private IDE $ide;
    private FormatPath $formatPath;

    private function __construct(IDE $ide, FormatPath $formatPath)
    {
        $this->ide = $ide;
        $this->formatPath = $formatPath;
    }

    public function __invoke(Application $app): Application
    {
        return $app
            ->map($this->operatingSystem())
            ->map($this->app());
    }

    public static function inApp(): self
    {
        return new self(IDE::unknown, new FormatPath\FullPath);
    }

    public function operatingSystem(): Middleware
    {
        return new Kernel\OperatingSystem;
    }

    public function app(): Middleware
    {
        return new Kernel\App($this->ide, $this->formatPath);
    }

    /**
     * @psalm-mutation-free
     */
    public function usingIDE(IDE $ide): self
    {
        return new self($ide, $this->formatPath);
    }

    /**
     * @psalm-mutation-free
     */
    public function removePathFromStackTrace(Url $path): self
    {
        return new self($this->ide, FormatPath\Truncate::of($path));
    }
}

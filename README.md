# Debug

[![Build Status](https://github.com/Innmind/Debug/workflows/CI/badge.svg?branch=master)](https://github.com/Innmind/Debug/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/Debug/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Debug)
[![Type Coverage](https://shepherd.dev/github/Innmind/Debug/coverage.svg)](https://shepherd.dev/github/Innmind/Debug)

[Profiler](https://github.com/Innmind/Profiler) client to help debug applications.

## Installation

```sh
composer require --dev innmind/debug
```

## Usage

```php
use Innmind\Framework\{
    Application,
    Main\Http, // this example also works with Main\Cli
    Middleware\Optional,
};
use Innmind\Profiler\Web\Kernel as Profiler;
use Innmind\Debug\Kernel as Debug;
use Innmind\Url\Path;

new class extends Http {
    protected function configure(Application $app): Application
    {
        return $app
            ->map(Optional::of(
                Debug::class,
                static fn() => Debug::inApp()->operatingSystem(),
            ))
            ->map(new YourAppKernel)
            ->map(Optional::of(
                Profiler::class,
                static fn() => Profiler::inApp(Path::of('var/profiler/')),
            ))
            ->map(Optional::of(
                Debug::class,
                static fn() => Debug::inApp()->app(),
            ));
    }
};
```

The first middleware will record calls to the operating system, you can omit this middleware if you don't want to record it. This middleware is defined first so all decorators to the `OperatingSystem` will be recorded, such as redirections for http calls.

> [!NOTE]
> if you don't want to record low level calls to the OS but the one initiated by your app you can define a single middleware via `Optional::of(Debug::class, static fn() => Debug::inApp())` as the last middleware.

The `Profiler` middleware adds the route `GET /_profiler/` where you'll find all the recorded profiles. The profiles are stored in the local `var/profiler/` in clear text.

The last middleware is the one that initiates the recording of profiles.

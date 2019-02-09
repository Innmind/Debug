# Debug

| `develop` |
|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Debug/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Debug/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Debug/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Debug/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/Debug/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Debug/build-status/develop) |

[Profiler](https://github.com/Innmind/Profiler) client to help debug applications.

## Installation

```sh
composer require --dev innmind/debug
```

## Usage

### Http

```php
use Innmind\HttpServer\Main;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Url;
use function Innmind\Debug\bootstrap;

new class extends Main {
    protected function main(ServerRequest $request, OperatingSystem $os): Response
    {
        $debug = debug($os, Url::fromString('http://localhost:8000')); // replace with the profiler url

        $handle = bootstrapYourApp($debug['os']()); // $handle must be an instance of Innmind\HttpFramework\RequestHandler
        $handle = $debug['http']($handle);

        return $handle($request);
    }
};
```

`bootstrapYourApp` is the function that create the request handler that is your whole app, replace this line by your real code.

By using `$debug['os']()` in your app it allows to logs in the profiler all processes and http requests made, and add most calls to the os in the call graph.

`$debug['http']()` wraps your request handler in order to automatically start a profile when a request is received and finish it when a response is sent or an exception is thrown.

### CLI

```php
use Innmind\CLI\{
    Main,
    Environment,
    Commands,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Url;
use function Innmind\Debug\bootstrap;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $debug = debug($os, Url::fromString('http://localhost:8000')); // replace with the profiler url

        $commands = bootstrapYourCommands($debug['os']()); // $commands bus be a set<Innmind\CLI\Command>

        $run = new Commands(...$debug['cli'](...$commands));
        $run($env);
    }
};
```

`bootstrapYourCommands` is the function that create all the commands available in your app, replace this line by your real code.

By using `$debug['os']()` in your app it allows to logs in the profiler all processes and http requests made, and add most calls to the os in the call graph.

`$debug['cli']()` wraps all your commands in order to automatically start a profile when a command is executed and finish it when the command is finished or an exception is thrown.

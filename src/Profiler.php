<?php
declare(strict_types = 1);

namespace Innmind\Debug;

use Innmind\Debug\Profiler\Profile\Identity;

/**
 * Even though multiple profiles can't be done in a single process ending the
 * profile requires to give the identity as a parameter so only the code that
 * started the profile can end it, thus preventing any other code to end it
 * prematurely (at least without some trickery)
 */
interface Profiler
{
    public function start(string $name): Identity;
    public function fail(Identity $identity, string $exit): void;
    public function succeed(Identity $identity, string $exit): void;
}

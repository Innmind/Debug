<?php
declare(strict_types = 1);

namespace Innmind\Debug\OperatingSystem\Remote;

use Innmind\Debug\Recorder\Beacon;
use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @internal
 */
final class Sql implements Connection
{
    private Connection $inner;
    private Beacon $beacon;

    private function __construct(Connection $inner, Beacon $beacon)
    {
        $this->inner = $inner;
        $this->beacon = $beacon;
    }

    public function __invoke(Query $query): Sequence
    {
        $parameters = $query
            ->parameters()
            ->map(static fn($parameter) => \sprintf(
                "%s: %s\n",
                $parameter->name()->match(
                    static fn($name) => $name,
                    static fn() => '?',
                ),
                (string) $parameter->value(),
            ))
            ->map(Str::of(...));
        $this->beacon->record()(
            static fn($mutation) => $mutation
                ->sections()
                ->remote()
                ->sql()
                ->record(Content::ofChunks(
                    Sequence::of($query->sql())
                        ->add("\n")
                        ->map(Str::of(...))
                        ->append($parameters),
                )),
        );

        return ($this->inner)($query);
    }

    public static function of(Connection $inner, Beacon $beacon): self
    {
        return new self($inner, $beacon);
    }
}

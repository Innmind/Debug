<?php
declare(strict_types = 1);

namespace Innmind\Debug\Recorder;

use Innmind\Debug\{
    Recorder,
    Record,
};

final class All implements Recorder
{
    /** @var list<Recorder> */
    private array $recorders;

    /**
     * @param list<Recorder> $recorders
     */
    private function __construct(array $recorders)
    {
        $this->recorders = $recorders;
    }

    /**
     * @no-named-arguments
     */
    public static function of(Recorder ...$recorders): self
    {
        return new self($recorders);
    }

    public function push(Record $record): void
    {
        foreach ($this->recorders as $recorder) {
            $recorder->push($record);
        }
    }
}

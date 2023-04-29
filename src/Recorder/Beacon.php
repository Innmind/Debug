<?php
declare(strict_types = 1);

namespace Innmind\Debug\Recorder;

use Innmind\Debug\{
    Recorder,
    Record,
};

final class Beacon implements Recorder
{
    private Record $record;

    public function __construct()
    {
        $this->record = new Record\Nothing;
    }

    public function record(): Record
    {
        return $this->record;
    }

    public function push(Record $record): void
    {
        $this->record = $record;
    }
}

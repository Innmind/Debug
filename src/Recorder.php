<?php
declare(strict_types = 1);

namespace Innmind\Debug;

/**
 * @internal
 */
interface Recorder
{
    public function push(Record $record): void;
}

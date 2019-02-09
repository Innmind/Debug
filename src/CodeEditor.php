<?php
declare(strict_types = 1);

namespace Innmind\Debug;

final class CodeEditor
{
    private static $sublimeText;
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function sublimeText(): self
    {
        return self::$sublimeText ?? self::$sublimeText = new self('sublime_text');
    }
}

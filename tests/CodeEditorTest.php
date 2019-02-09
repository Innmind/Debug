<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug;

use Innmind\Debug\CodeEditor;
use PHPUnit\Framework\TestCase;

class CodeEditorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(CodeEditor::class, CodeEditor::sublimeText());
        $this->assertSame(CodeEditor::sublimeText(), CodeEditor::sublimeText());
    }
}

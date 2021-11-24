<?php

use PHPUnit\Framework\TestCase;

final class TextTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function testMarkTitle()
    {
        $this->assertSame(
            <<<'EOS'
            <h2>1. Head2</h2>
            <p>text</p>
            <h3>1.1 Head3</h3>
            <p>text</p>
            EOS,
            Text::markTitle(<<<'EOS'
            <p>1. Head2</p>
            <p>text</p>
            <p>1.1 Head3</p>
            <p>text</p>
            EOS)
        );
    }
}

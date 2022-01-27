<?php

use PHPUnit\Framework\TestCase;

final class ImageParserTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'src/setup.php';
    }

    public function test()
    {
        $url = 'http://lasys/a.jpg';
        $imageParser = ImageParser::url($url);
        $this->assertFalse(isset($imageParser->title));
        $this->assertSame(
            join(PHP_EOL, [
                '<div class="image">',
                '<img src="http://lasys/a.jpg?' . Server::QUERY_GET_RAW . '" />',
                '</div>',
            ]),
            $imageParser->content
        );
    }
}

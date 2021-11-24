<?php
final class Text
{
    private function __construct()
    {
    }

    public static function markTitle($html)
    {
        // comma, period, colon, exclamation, question
        $notPunc = '[^\x{FF0C}\x{3002}\x{FF1A}\x{FF01}\x{FF1F},.:!?;=<>_]';
        $html = preg_replace_callback(
            '/^<p>(\d+(?:\.\d+)*\.?)\s+(' . $notPunc . '+)<\/p>$/mu',
            function ($m) use (&$lv) {
                $lv = 2 + count(explode('.', rtrim($m[1], '.'))) - 1;
                $openTag = '<h' . $lv . '>';
                $closeTag = '</h' . $lv . '>';
                return $openTag . $m[1] . ' ' . $m[2] . $closeTag;
            },
            $html
        );
        return $html;
    }
}

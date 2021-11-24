<?php
final class Str
{
    private function __construct()
    {
    }

    public static function captalize($str)
    {
        $words = explode('_', $str);
        foreach ($words as &$word) {
            $word = ucfirst($word);
        }
        return implode(' ', $words);
    }

    public static function filterLinks($txt)
    {
        return preg_replace(
            '/(?:ftp|https?):\/\/[^\)\s]*/',
            '<a href="$0" target="_blank">$0</a>',
            $txt
        );
    }
}

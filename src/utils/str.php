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

    public static function pathUrl($path)
    {
        return str_replace(DS, '/', $path);
    }

    public static function filterLinks($txt)
    {
        return preg_replace(
            '/(?:ftp|https?):\/\/[^\)\s]*/',
            '<a href="$0" target="_blank">$0</a>',
            $txt
        );
    }

    public static function classToFile($class)
    {
        $words = preg_split('/(?=[A-Z])/', $class, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as &$word) {
            $word = strtolower($word);
        }
        return implode('_', $words);
    }

    public static function isValidFileName($name)
    {
        return preg_match('/^[A-Za-z\d][\w\-.]+$/', $name) > 0;
    }
}

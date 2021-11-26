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

    public static function classToFile($class)
    {
        $words = preg_split('/(?=[A-Z])/', $class, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as &$word) {
            $word = strtolower($word);
        }
        return implode('_', $words) . '.php';
    }

    public static function splitFunParas($fun)
    {
        $matches = [];
        $paras = [];
        if (preg_match('/^(\w+)\((.*)\)$/', $fun, $matches)) {
            $fun = $matches[1];
            if (!empty($matches[2])) {
                $paras = array_map('trim', explode(',', $matches[2]));
            }
        }
        return [$fun, $paras];
    }

    public static function isValidFileName($name)
    {
        return preg_match('/^[A-Za-z\d][\w\-.]+$/', $name) > 0;
    }
}

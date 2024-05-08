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

    public static function filterEmoji($txt)
    {
        return preg_replace(
            ['/:\(/', '/:\)/'],
            ["\u{1F641}", "\u{1F642}"],
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

    public static function timeStr($timestamp)
    {
        return date('Y.m.d H:i:s', $timestamp);
    }

    public static function fileInfo($info)
    {
        $msg = '';
        if (isset($info['time'])) {
            $msg .= Icon::TIME . '<em>' . Str::timeStr($info['time']) . '</em> ';
        }
        if (isset($info['uname'])) {
            $msg .= Icon::USER . $info['uname'];
        }
        return $msg;
    }

    public static function fileInfoText($info)
    {
        $msg = '';
        if (isset($info['uname'])) {
            $msg .= $info['uname'];
        }
        if (isset($info['time'])) {
            $msg .= ' @ ' . Str::timeStr($info['time']);
        }
        return $msg;
    }

    public static function bytesXor($a, $b)
    {
        $la = strlen($a);
        $lb = strlen($b);
        $size = max($la, $lb);
        $r = str_pad($a, $size, chr(0));
        for ($i = 0; $i < $lb; ++$i) {
            $r[$i] = chr(ord($r[$i]) ^ ord($b[$i]));
        }
        return $r;
    }
}

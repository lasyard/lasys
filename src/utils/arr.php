<?php
final class Arr
{
    private function __construct()
    {
    }

    public static function transKeys($arr, ...$keys)
    {
        $result = [];
        self::copyKeys($result, $arr, ...$keys);
        return $result;
    }

    public static function copyKeys(&$target, $arr, ...$keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $arr)) {
                $target[$key] = $arr[$key];
            }
        }
    }
}

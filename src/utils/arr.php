<?php
final class Arr
{
    private function __construct()
    {
    }

    public static function transKeys($arr, ...$keys)
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $arr)) {
                $result[$key] = $arr[$key];
            }
        }
        return $result;
    }
}

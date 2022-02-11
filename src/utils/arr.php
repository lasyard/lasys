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

    public static function copyNonExistingKeys(&$target, $arr, ...$keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $arr) && !array_key_exists($key, $target)) {
                $target[$key] = $arr[$key];
            }
        }
    }

    public static function toArray($obj)
    {
        if (!isset($obj)) {
            return [];
        } else if (!is_array($obj)) {
            return [$obj];
        }
        return $obj;
    }

    public static function makeArray(&$obj)
    {
        $obj = self::toArray($obj);
    }

    public static function forOneOrMany(&$objs, $fun)
    {
        if (is_array($objs)) {
            foreach ($objs as &$obj) {
                $fun($obj);
            }
        } else {
            $fun($objs);
        }
    }

    public static function uniqueMerge($a1, $a2)
    {
        return array_unique(array_merge(self::toArray($a1), self::toArray($a2)));
    }
}

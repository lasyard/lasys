<?php
final class Common
{
    private function __construct() {}

    public static function getOutput($fun, $params = [])
    {
        ob_start();
        try {
            $result = call_user_func_array($fun, $params);
        } catch (Exception $e) {
            ob_get_clean();
            throw $e;
        }
        $buffer = ob_get_clean();
        if (empty($buffer)) {
            return $result;
        }
        return $buffer;
    }

    public static function cmp($a, $b)
    {
        return $a <=> $b;
    }

    public static function chainCmps($aFunc, $bFunc)
    {
        return function ($a, $b) use ($aFunc, $bFunc) {
            $r = $aFunc($a, $b);
            if ($r != 0) {
                return $r;
            }
            return $bFunc($a, $b);
        };
    }

    public static function invertCmp($func)
    {
        return function ($a, $b) use ($func) {
            return $func($b, $a);
        };
    }

    public static function cmpIndex($index, $func)
    {
        return function ($a, $b) use ($index, $func) {
            if ($a[$index] ?? false) {
                if ($b[$index] ?? false) {
                    return $func($a[$index], $b[$index]);
                } else {
                    return -1;
                }
            }
            if ($b[$index] ?? false) {
                return 1;
            }
            return 0;
        };
    }
}

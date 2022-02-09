<?php
final class Common
{
    private function __construct()
    {
    }

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

    public static function funCmpBy($index, $descend = true)
    {
        if ($descend) {
            return function ($b, $a) use ($index) {
                return $a[$index] <=> $b[$index];
            };
        }
        return function ($a, $b) use ($index) {
            return $a[$index] <=> $b[$index];
        };
    }
}

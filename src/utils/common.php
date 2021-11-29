<?php
final class Common
{
    private function __construct()
    {
    }

    public static function getOutput($fun, $params)
    {
        ob_start();
        try {
            $result = call_user_func_array($fun, $params);
        } catch (Exception $e) {
            ob_get_clean();
            throw $e;
        }
        $buffer = ob_get_clean();
        if (empty($result)) {
            $result = $buffer;
        }
        return $result;
    }
}

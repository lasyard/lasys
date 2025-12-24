<?php
final class SysActions extends Actions
{
    public function actionPhpinfo()
    {
        phpinfo();
    }

    public function actionXdebugInfo()
    {
        if (function_exists('xdebug_info')) {
            // call dynamically to avoid undefined function errors in static analysis
            call_user_func('xdebug_info');
        } else {
            echo 'Xdebug is not installed.';
        }
    }
}

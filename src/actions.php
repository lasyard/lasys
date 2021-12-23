<?php
class Actions
{
    use Getter;

    private static $_cache = [];

    private $_method;
    private $_args;

    private $_pathVars;
    private $_base;
    private $_path;
    private $_name;
    private $_content;

    protected $_httpHeaders = [];
    protected $_title;

    public static function __callStatic($method, $args)
    {
        if (count($args) == 0) {
            $class = static::class;
            if (!isset(self::$_cache[$class][$method])) {
                self::$_cache[$class][$method] = new static($method);
            }
            return self::$_cache[$class][$method];
        }
        $actions = new static($method, $args);
        if (!$actions instanceof Actions) {
            throw new Exception('Actions must inhrerit class "' . self::class . '".');
        }
        return $actions;
    }

    public function __construct($method, $args = [])
    {
        $name = 'action' . ucfirst($method);
        if (!method_exists($this, $name)) {
            throw new RuntimeException('Cannot find action method "' . get_class($this) . '::' . $name . '".');
        }
        $this->_method = $name;
        $this->_args = $args;
    }

    public function do($pathVars, $base, $path, $name)
    {
        $this->_pathVars = $pathVars;
        $this->_base = $base;
        $this->_path = $path;
        $this->_name = $name;
        try {
            $this->_content = Common::getOutput([$this, $this->_method], $this->_args);
        } catch (Exception $e) {
            $this->doError($e->getMessage());
        }
    }

    public function doError($msg)
    {
        $this->_title = 'Error';
        $this->_content = View::renderHtml('error', ['message' => $msg]);
    }

    public function actionDefault(...$args)
    {
        $this->_title = 'default';
        echo <<<EOS
        <h1>Actions::default</h1>
        <p class="sys">
        Generated by default action of <a href="https://github.com/lasyard/lasys" target="_blank">Lasys</a>.
        </p>
        EOS;
        echo '<p>pathVars = ';
        print_r($this->pathVars);
        echo '</p>';
        echo '<p>args = ';
        print_r($args);
        echo '</p>';
    }

    public function actionError(...$args)
    {
        $this->doError($args[0]);
    }

    protected function hasPriv($key = Server::GET)
    {
        return Sys::app()->hasPriv($this->_name, $key);
    }

    protected function conf()
    {
        return Sys::app()->conf();
    }

    protected function info()
    {
        return Sys::app()->info($this->_name);
    }
}

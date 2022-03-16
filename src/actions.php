<?php
class Actions
{
    use Getter;

    public const ACTION = 'action';
    public const PRIV = 'priv';

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
            $actions = self::$_cache[$class][$method];
        } else {
            $actions = new static($method, $args);
        }
        return $actions;
    }

    // As a stub to set priv of directory.
    public static function noop(...$priv)
    {
        return [
            self::ACTION => null,
            self::PRIV => $priv
        ];
    }

    public function priv(...$priv)
    {
        return [
            self::ACTION => $this,
            self::PRIV => $priv
        ];
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

    protected function default($confName)
    {
        return $this->conf($confName) ?? Sys::app()->conf($confName);
    }

    public function do($pathVars, $base, $path, $name)
    {
        $this->_pathVars = $pathVars;
        $this->_base = $base;
        $this->_path = $path;
        $this->_name = $name;
        try {
            $this->_content = Common::getOutput([$this, $this->_method], $this->_args);
            if (Sys::db()->inTransaction()) {
                Sys::db()->commit();
            }
        } catch (Exception $e) {
            if (Sys::db()->inTransaction()) {
                Sys::db()->rollBack();
            }
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
        throw new RuntimeException(...$args);
    }

    protected function hasPrivOf($type, $uid = null)
    {
        return Sys::app()->hasPrivOf($this->_name, $type, $uid);
    }

    protected function conf($name)
    {
        $list = Sys::app()->conf(Config::LIST);
        return $list[$this->_name][$name] ?? null;
    }
}

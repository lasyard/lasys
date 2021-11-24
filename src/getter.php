<?php
trait Getter
{
    public function __call($name, $args)
    {
        $var = '_' . $name;
        if (property_exists($this, $var)) {
            return $this->{$var};
        }
        throw new Exception('Try to call undefined method "' . get_class($this) . '::' . $name . '".');
    }

    public function __get($var)
    {
        return $this->{$var}();
    }
}

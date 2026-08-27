<?php
final class Recursive extends RecursiveTraits
{
    private $_key;

    public function __construct($key)
    {
        $this->_key = $key;
    }

    public function forChild(&$conf, $oldConf)
    {
        if (isset($oldConf[$this->_key])) {
            $conf[$this->_key] = Arr::uniqueMerge($conf[$this->_key] ?? [], $oldConf[$this->_key]);
        }
        parent::forChild($conf, $oldConf);
    }
}

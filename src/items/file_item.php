<?php
abstract class FileItem
{
    use Getter;

    protected $_file;

    public function __construct($file)
    {
        $this->_file = $file;
    }

    public static function get($path, $name)
    {
        $files = glob($path . '/' . $name . '.*');
        if (!$files) {
            throw new Exception('Cannot find item "' . $name . '".');
        }
        $file = $files[0];
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $class = ucfirst($ext) . 'Item';
        return new $class($file);
    }

    public function httpHeaders()
    {
        $mtime = filemtime($this->_file);
        return [
            'Cache-Control: public, max-age=345600',
            'ETag: "' . $mtime . '"',
            'Last-Modified: ' . gmdate(DATE_RFC7231, $mtime),
        ];
    }
}

<?php
class FileItem
{
    use Getter;

    protected $_file;

    public function __construct($file)
    {
        $this->_file = $file;
    }

    public static function get($path, $name, $check = true)
    {
        $files = glob($path . '/' . $name . '.*');
        if (!$files) {
            throw new RuntimeException('Cannot find file "' . $name . '".');
        }
        $file = $files[0];
        if (!$check) {
            return new FileItem($file);
        }
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'txt':
                return new TextItem($file);
            default:
                throw new RuntimeException('Unsupported file type "' . $ext . '".');
        }
    }

    public function time()
    {
        return filemtime($this->_file);
    }

    public function delete()
    {
        unlink($this->_file);
    }
}

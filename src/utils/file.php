<?php
final class File
{
    public const NO_FILE_SENT = 10001;

    private function __construct()
    {
    }

    public static function mkdirForFile($file)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir(dirname($file), 0775, true);
        }
    }

    public static function openForWriting($file)
    {
        self::mkdirForFile($file);
        $fh = @fopen($file, 'w');
        if ($fh) {
            return $fh;
        }
        throw new RuntimeException('Open file "' . $file . '" for writing failed!');
    }

    public static function upload($path, $fileName, $overwrite, $sizeLimit)
    {
        $file = $_FILES['file'];
        // Handle uploading.
        if (is_array($file['error'])) {
            throw new RuntimeException('Only one file allowed.');
        }
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.', self::NO_FILE_SENT);
                return;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded file size limit "' . $sizeLimit . '".');
            default:
                throw new RuntimeException('Unknown errors "' . $file['error'] . '".');
        }
        if ($file['size'] > $sizeLimit) {
            throw new RuntimeException('Exceeded file size limit "' . $sizeLimit . '".');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Uploaded file error!');
        }
        $name = $fileName ?? $file['name'];
        $newFile = $path . DS . $name;
        if (!$overwrite && file_exists($newFile)) {
            throw new RuntimeException('File "' . $name . '" exists.');
        }
        self::mkdirForFile($newFile);
        if (!Str::isValidFileName($name)) {
            throw new RuntimeException('Invalid file name \"' . $name . '\".');
        }
        if (!move_uploaded_file($file['tmp_name'], $newFile)) {
            throw new RuntimeException('Cannot save uploaded file "' . $name . '".');
        }
        return $name;
    }
}

<?php
final class FileActions extends Actions
{
    public const FILE_SIZE_LIMIT = 65536;
    public const FILE_FIELD_NAME = 'file';

    private function getParser($name)
    {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'txt':
                return new TextParser($this->path . DS . $name);
            case 'png':
            case 'jpg':
                return new ImageParser($this->base . $name);
            default:
                throw new RuntimeException('Unsupported file type "' . $ext . '".');
        }
    }

    public function actionGet()
    {
        $name = $this->name;
        $file = $this->path . DS . $name;
        if (!is_file($file)) {
            throw new RuntimeException('Cannot find file "' . $name . '".');
        }
        $this->_httpHeaders = [
            'Cache-Control: no-cache',
            'ETag: "' . md5(Sys::user()->name . filemtime($file)) . '"',
        ];
        $parser = $this->getParser($name);
        $this->_title = $parser->title;
        echo $parser->content;
    }

    public function actionPut($sizeLimit = self::FILE_SIZE_LIMIT)
    {
        $this->doUpload($sizeLimit, $this->name, true);
    }

    public function actionDelete()
    {
        $name = $this->name;
        unlink($this->path . DS . $name);
        View::render('deleted', ['name' => $name, 'url' => $this->base]);
    }

    public function actionPost($sizeLimit = self::FILE_SIZE_LIMIT)
    {
        $this->doUpload($sizeLimit);
    }

    public function actionUploadForm($title, $accept = '*', $sizeLimit = self::FILE_SIZE_LIMIT)
    {
        View::render('upload', [
            'title' => $title,
            'fieldName' => self::FILE_FIELD_NAME,
            'action' => $this->base,
            'accept' => $accept,
            'sizeLimit' => $sizeLimit,
        ]);
    }

    private function doUpload($sizeLimit = 65536, $fileName = null, $overwrite = false)
    {
        $file = $_FILES[self::FILE_FIELD_NAME];
        // Handle uploading.
        if (is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters.');
        }
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded file size limit "' . $sizeLimit . '".');
            default:
                throw new RuntimeException('Unknown errors.');
        }
        if ($file['size'] > $sizeLimit) {
            throw new RuntimeException('Exceeded file size limit "' . $sizeLimit . '".');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Uploaded file error!');
        }
        $path = $this->path;
        $name = $fileName ?? $file['name'];
        $newFile = $path . DS . $name;
        if (!$overwrite && (is_file($newFile) || is_dir($newFile))) {
            throw new RuntimeException('File "' . $name . '" exists.');
        }
        if (
            Str::isValidFileName($name)
            && move_uploaded_file($file['tmp_name'], $newFile)
        ) {
            $user = Sys::user();
            $info = [
                'time' => $_SERVER['REQUEST_TIME'],
                'uid' => $user->id,
                'uname' => $user->name,
            ];
            $parser = $this->getParser($name);
            if ($parser->title) {
                $info['title'] = $parser->title;
            }
            Sys::app()->addFile($name, $info);
            Sys::app()->redirect($name);
        }
        throw new RuntimeException('Cannot save uploaded file "' . $name . '".');
    }
}

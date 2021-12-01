<?php
final class FileActions extends Actions
{
    public const FILE_FIELD_NAME = 'file';

    public function actionGet()
    {
        $path = $this->path;
        $name = $this->name;
        $item = FileItem::get($path, $name);
        $mtime = $item->time();
        $this->_httpHeaders = [
            'Cache-Control: no-cache',
            'ETag: "' . md5(Sys::user()->name . $mtime) . '"',
        ];
        $this->_title = $item->title;
        return $item->content;
    }

    public function actionPut()
    {
    }

    public function actionDelete()
    {
        $path = $this->path;
        $name = $this->name;
        $item = FileItem::get($path, $name, false);
        $item->delete();
        View::render('deleted', ['name' => $name, 'url' => $this->base]);
    }

    public function actionPost($sizeLimit = 65536)
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
        $name = $file['name'];
        if (
            Str::isValidFileName($name)
            && move_uploaded_file($file['tmp_name'], $path . '/' . $name)
        ) {
            $bn = pathinfo($name, PATHINFO_FILENAME);
            $item = FileItem::get($path, $bn);
            Sys::app()->addFile($bn, [
                'title' => $item->title,
                'time' => $_SERVER['REQUEST_TIME'],
                'user' => Sys::user()->name,
            ]);
            Sys::app()->redirect($bn);
        }
        throw new RuntimeException('Cannot save uploaded file "' . $name . '".');
    }

    public function actionUploadForm($title, $accept = '*', $sizeLimit = 65536)
    {
        View::render('upload', [
            'title' => $title,
            'fieldName' => self::FILE_FIELD_NAME,
            'action' => $this->base,
            'accept' => $accept,
            'sizeLimit' => $sizeLimit,
        ]);
    }
}

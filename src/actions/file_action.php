<?php
final class FileAction extends Action
{
    public function upload($title, $accept = '*', $fieldName = 'file', $sizeLimit = 65536)
    {
        if (!isset($_FILES[$fieldName]['error'])) {
            // Show upload form.
            View::render('upload', [
                'title' => $title,
                'fieldName' => $fieldName,
                'sizeLimit' => $sizeLimit,
                'accept' => $accept,
            ]);
            return;
        }
        $file = $_FILES[$fieldName];
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
        $path = Sys::app()->path();
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
}

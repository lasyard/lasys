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
        if (
            Str::isValidFileName($file['name'])
            && move_uploaded_file($file['tmp_name'], Sys::app()->path() . '/' . $file['name'])
        ) {
            echo '<p class="sys">File "', $file['name'], '" is uploaded successfully.</p>';
            return;
        }
        throw new RuntimeException('Cannot save uploaded file "' . $file['name'] . '".');
    }
}

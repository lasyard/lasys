<?php
final class FileActions extends Actions
{
    // configs
    public const UPLOAD_TITLE = 'file:uploadTitle';
    public const SIZE_LIMIT = 'file:sizeLimit';
    public const ACCEPT = 'file:accept';
    public const ORDER = 'file:order';

    public const DEFAULT = [
        self::UPLOAD_TITLE => 'Upload',
        self::SIZE_LIMIT => 65536,
        self::ACCEPT => 'text/plain',
    ];

    public const FILE_FIELD_NAME = 'file';
    public const UPLOAD_ITEM = '-upload-';

    public static function default($confName)
    {
        return Sys::app()->conf($confName) ?? static::DEFAULT[$confName] ?? null;
    }

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

    private function buildMeta()
    {
        $buttons = [];
        $editForm = null;
        if ($this->hasPrivOf(Server::POST_UPDATE)) {
            $buttons[] = '<span id="-meta-btn-edit-">' . Icon::EDIT . '</span>';
            $editForm = View::renderHtml('upload', [
                'title' => Icon::EDIT . ' ' . $this->name,
                'fieldName' => self::FILE_FIELD_NAME,
                'action' => Server::QUERY_POST_UPDATE,
                'accept' => self::default(self::ACCEPT),
                'sizeLimit' => self::default(self::SIZE_LIMIT),
            ]);
        }
        if ($this->hasPrivOf(Server::AJAX_DELETE)) {
            $buttons[] = '<span id="-meta-btn-delete-">' . Icon::DELETE . '</span>';
        }
        return ['buttons' => $buttons, 'editForm' => $editForm];
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
        $info = $this->info($name);
        if ($info) {
            View::render('meta', array_merge($info, $this->buildMeta()));
        }
        echo $parser->content;
    }

    public function actionAjaxDelete()
    {
        $name = $this->name;
        unlink($this->path . DS . $name);
        View::render('deleted', ['name' => $name, 'url' => $this->base]);
    }

    public function actionPost()
    {
        $this->doUpload();
    }

    public function actionPostUpdate()
    {
        $this->doUpload($this->name, true);
    }

    public function actionUploadForm()
    {
        View::render('upload', [
            'title' => self::default(self::UPLOAD_TITLE),
            'fieldName' => self::FILE_FIELD_NAME,
            'action' => $this->base,
            'accept' => self::default(self::ACCEPT),
            'sizeLimit' => self::default(self::SIZE_LIMIT),
        ]);
    }

    private function doUpload($fileName = null, $overwrite = false)
    {
        $sizeLimit = self::default(self::SIZE_LIMIT);
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
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
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

    public static function action($readOnly, $type)
    {
        if ($readOnly) {
            if ($type == Server::GET) {
                return FileActions::get()->priv();
            }
        } else {
            switch ($type) {
                case Server::GET:
                    return  FileActions::get()->priv();
                    break;
                case Server::POST_UPDATE:
                    return  FileActions::postUpdate()->priv(User::OWNER, User::EDIT);
                    break;
                case Server::AJAX_DELETE:
                    return  FileActions::ajaxDelete()->priv(User::OWNER, User::EDIT);
                    break;
                default:
                    break;
            }
        }
        return null;
    }

    public static function byTime($descend = true)
    {
        if ($descend) {
            return function ($b, $a) {
                return $a['time'] <=> $b['time'];
            };
        }
        return function ($a, $b) {
            return $a['time'] <=> $b['time'];
        };
    }

    public static function byName($descend = false)
    {
        if (!$descend) {
            return function ($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            };
        }
        return function ($b, $a) {
            return strnatcasecmp($a['name'], $b['name']);
        };
    }
}

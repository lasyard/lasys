<?php
final class GalleryActions extends Actions
{
    private const THUMB_DIR = 'thumbs';

    public const THUMB_SIZE = 'gallery:thumbSize';
    public const KEEP_NAME = 'gallery:keepName';

    public const DEFAULT = [
        self::THUMB_SIZE => 128,
        FileActions::SIZE_LIMIT => 65536 * 8,
        FileActions::ACCEPT => 'image/*',
        self::KEEP_NAME => false,
    ];

    private static function relPath($path)
    {
        $len = strlen(DATA_PATH);
        if (substr($path, 0, $len) === DATA_PATH) {
            return substr($path, $len);
        }
        throw new RuntimeException('The path "' . $path . '" is not in "DATA_PATH".');
    }

    private static function thumbFile($file)
    {
        return PUB_PATH . DS . self::THUMB_DIR . self::relPath($file);
    }

    private static function fileName($file, $time)
    {
        return date('Ymd', $time)
            . '_' . substr(md5_file($file), 0, 8)
            . '.' . strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    protected function default($confName)
    {
        return parent::default($confName) ?? self::DEFAULT[$confName] ?? null;
    }

    private function buildRibbon()
    {
        $formUpload = null;
        $btnUpload = null;
        if ($this->hasPrivOf(Server::POST)) {
            $btnUpload = Icon::UPLOAD;
            $formUpload = View::renderHtml('upload', [
                'title' => Icon::EDIT . ' ' . ($this->default(FileActions::UPLOAD_TITLE) ?? $this->name),
                'action' => $this->base . $this->name,
                'accept' => $this->default(FileActions::ACCEPT),
                'sizeLimit' => $this->default(FileActions::SIZE_LIMIT),
            ]);
        }
        return [
            'btnCheck' => Sys::user()->hasPriv(User::ADMIN) ? Icon::DB_CHECK : null,
            'btnUpload' => $btnUpload,
            'formUpload' => $formUpload,
        ];
    }

    private function hasThumbnail()
    {
        return $this->default(self::THUMB_SIZE) > 0;
    }

    public function actionGet()
    {
        $this->configScriptsAndStyles();
        Sys::app()->addScript('js' . DS . 'gallery');
        $padSize = $this->default(self::THUMB_SIZE);
        if ($padSize > 0) {
            $padSize += 100;
            Sys::app()->addCss(<<<EOS
        div#main div.gallery div.thumbs a.thumb {
            width: {$padSize}px;
            height: {$padSize}px;
            line-height: {$padSize}px;
        }
        EOS);
        }
        $ribbon = $this->buildRibbon();
        View::render('gallery_ribbon', $ribbon);
    }

    public function actionAjaxGet()
    {
        $files = Meta::loadFileList($this->path . DS . $this->name);
        $images = [];
        $conf = Sys::app()->readConf($this->name);
        foreach ($files as $name => $info) {
            $deleteAction = $conf->action($name, Server::AJAX_DELETE);
            $updateAction = $conf->action($name, Server::AJAX_UPDATE);
            $uid = $info['uid'] ?? User::ADMIN;
            $images[] = [
                'name' => $name,
                'title' => $info['title'] ?? ($this->conf(self::KEEP_NAME) ? $name : ''),
                'time' => $info['time'] ?? 0,
                'user' => $info['uname'] ?? 'Anonymous',
                'delete' => $deleteAction != null && Sys::user()->hasPrivs($deleteAction[Actions::PRIV], $uid),
                'update' => $updateAction != null && Sys::user()->hasPrivs($updateAction[Actions::PRIV], $uid),
            ];
        }
        $order = $this->conf(Config::ORDER);
        if ($order) {
            usort($images, $order);
        }
        $res = [
            'image' => [
                'prefix' => $this->base . $this->name . '/',
                'suffix' => '?' . Server::QUERY_GET_RAW,
            ],
            'list' => $images,
        ];
        if ($this->hasThumbnail()) {
            $res['thumb'] = [
                'prefix' => PUB_URL . self::THUMB_DIR
                    . str_replace(DS, '/', self::relPath($this->path))
                    . '/' . $this->name . '/',
                'suffix' => '',
            ];
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function actionPost()
    {
        $path = $this->path . DS . $this->name;
        $origName = null;
        $tempName = File::upload($path, function ($file) use (&$origName) {
            $origName = $file['name'];
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            return bin2hex(random_bytes(8)) . '.' . $ext;
        }, false, $this->default(FileActions::SIZE_LIMIT));
        $tempFile = $path . DS . $tempName;
        if ($this->default(self::KEEP_NAME)) {
            $name = $origName;
            $file = $path . DS . $name;
            if (file_exists($file)) {
                unlink($tempFile);
                throw new RuntimeException('Image "' . $name . '" exists.');
            }
        } else {
            $name = 'temp_' . $tempName;
            $file = $path . DS . $name;
        }
        Image::optimizeJpegFile($tempFile, $file);
        $time = Image::getExifDate($tempFile);
        if (!$time) {
            $time = $_SERVER['REQUEST_TIME'];
        }
        unlink($tempFile);
        if (!$this->default(self::KEEP_NAME)) {
            $newName = self::fileName($file, $time);
            $newFile = $path . DS . $newName;
            if (file_exists($newFile)) {
                unlink($file);
                throw new RuntimeException('Image "' . $newName . '" exists.');
            }
            rename($file, $newFile);
            $name = $newName;
            $file = $newFile;
        }
        $user = Sys::user();
        $info = [
            'time' => $time,
            'uid' => $user->id,
            'uname' => $user->name,
        ];
        if (!empty($_POST['title'])) {
            $info['title'] = $_POST['title'];
        }
        $meta = Meta::load($path);
        $meta[$name] = $info;
        Meta::save($path, $meta);
        if ($this->hasThumbnail()) {
            $thumb = self::thumbFile($file);
            if ($thumb) {
                $size = $this->default(self::THUMB_SIZE);
                Image::createThumbnail($file, $thumb, $size, $size);
            } else {
                throw new RuntimeException("Cannot create thumb for this file.");
            }
        }
        Sys::app()->redirect($this->name);
    }

    public function actionAjaxDelete()
    {
        $name = $this->name;
        $file = $this->path . DS . $name;
        if (unlink($file)) {
            Msg::info('Succeeded to delete image file "' . $name . '".');
        } else {
            Msg::warn('Failed to delete image file "' . $name . '".');
        }
        $thumb = self::thumbFile($file);
        if (is_file($thumb)) {
            if (unlink($thumb)) {
                Msg::info('Succeeded to delete image thumbnail "' . $name . '".');
            } else {
                Msg::warn('Failed to delete image thumbnail "' . $name . '".');
            }
        }
    }

    public function actionAjaxUpdate()
    {
        $title = file_get_contents('php://input');
        $title = trim($title);
        $name = $this->name;
        if (!empty($title)) {
            $meta = Meta::load($this->path);
            $meta[$name]['title'] = $title;
            Meta::save($this->path, $meta);
            Msg::info('Set the title of image "' . $name . '" to "' . $title . '".');
        } else {
            Msg::warn('The title of image "' . $name . '" is not set.');
        }
    }

    public function actionCheck()
    {
        $galleryPath = $this->path . DS . $this->name;
        $files = Meta::loadFileList($galleryPath);
        if ($this->conf(self::KEEP_NAME)) {
            Msg::info("Keep name is enabled, so the file name will not be checked.");
            return;
        }
        $count = 0;
        $thumbCount = 0;
        $meta = Meta::load($galleryPath);
        foreach ($files as $name => &$info) {
            $file = $galleryPath . DS . $name;
            $time = $info['time'] ?? time();
            $rightName = self::fileName($file, $time);
            if ($name !== $rightName) {
                ++$count;
                $rightFile = $galleryPath . DS . $rightName;
                rename($galleryPath . DS . $name, $rightFile);
                $meta[$rightName] = [
                    'title' => $info['title'] ?? 'untitled',
                    'time' => $time,
                    'uid' => $info['uid'] ?? User::ADMIN,
                    'uname' => $info['uname'] ?? 'Anonymous',
                ];
                if ($this->hasThumbnail()) {
                    ++$thumbCount;
                    $rightThumb = self::thumbFile($rightFile);
                    rename(self::thumbFile($file), $rightThumb);
                }
            }
        }
        if ($count > 0) {
            Meta::save($galleryPath, $meta);
            Msg::warn("There are $count images and $thumbCount thumbnails with wrong name, which have been renamed.");
        } else {
            Msg::info("All images have correct name.");
        }
    }
}

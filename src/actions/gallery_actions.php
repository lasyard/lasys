<?php
final class GalleryActions extends Actions
{
    private const THUMB_DIR = 'thumbs';

    public const THUMB_SIZE = 'gallery:thumbSize';

    public const DEFAULT = [
        self::THUMB_SIZE => 128,
        FileActions::SIZE_LIMIT => 65536 * 8,
        FileActions::ACCEPT => 'image/*',
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

    public function default($confName)
    {
        return $this->conf($confName) ?? self::DEFAULT[$confName] ?? null;
    }

    private function buildRibbon()
    {
        $formUpload = null;
        $btnUpload = null;
        if ($this->hasPrivOf(Server::POST)) {
            $btnUpload = Icon::UPLOAD;
            $formUpload = View::renderHtml('upload', [
                'title' => Icon::EDIT . ' ' . $this->name,
                'accept' => $this->default(FileActions::ACCEPT),
                'sizeLimit' => $this->default(FileActions::SIZE_LIMIT),
            ]);
        }
        return [
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
        Sys::app()->addScript('js' . DS . 'gallery');
        $ribbon = $this->buildRibbon();
        View::render('gallery_ribbon', $ribbon);
    }

    public function actionAjaxGet()
    {
        $files = Meta::loadFileList($this->path . DS . $this->name);
        $images = [];
        $conf = Sys::app()->readConf($this->name);
        foreach ($files as $name => $info) {
            $delAction = $conf->action($name, Server::AJAX_DELETE);
            $images[] = [
                'name' => $name,
                'title' => $info['title'] ?? '',
                'time' => $info['time'] ?? 0,
                'user' => $info['uname'] ?? 'Anonymous',
                'delete' => $delAction != null && isset($info['uid'])
                    && Sys::user()->hasPrivs($delAction[Actions::PRIV], $info['uid']),
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
        $origName = File::upload($path, null, false, $this->default(FileActions::SIZE_LIMIT));
        $origFile = $path . DS . $origName;
        $name = sha1_file($origFile) . '.' . pathinfo($origName, PATHINFO_EXTENSION);
        $file = $path . DS . $name;
        if (file_exists($file)) {
            unlink($origFile);
            throw new RuntimeException('Image "' . $name . '" exists.');
        }
        Image::optimizeJpegFile($origFile, $file);
        $time = Image::getExifDate($origFile);
        unlink($origFile);
        if (!$time) {
            $time = $_SERVER['REQUEST_TIME'];
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
}

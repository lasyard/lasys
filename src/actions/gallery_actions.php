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

    private static function thumbFile($file)
    {
        $len = strlen(DATA_PATH);
        if (substr($file, 0, $len) === DATA_PATH) {
            return PUB_PATH . DS . self::THUMB_DIR . substr($file, $len);
        }
        return false;
    }

    private static function thumbUrl($file)
    {
        $len = strlen(DATA_PATH);
        if (substr($file, 0, $len) === DATA_PATH) {
            return PUB_URL . self::THUMB_DIR . str_replace(DS, '/', substr($file, $len));
        }
        return false;
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
            'msg' => $this->conf(Config::TITLE) ?? Str::captalize($this->name),
            'btnUpload' => $btnUpload,
            'formUpload' => $formUpload,
        ];
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
        $fileList = [];
        foreach ($files as $name => $info) {
            $fileList[] = [
                'file' => $this->base . $this->name . '/' . $name . '?' . Server::QUERY_GET_RAW,
                'thumb' => self::thumbUrl($this->path . DS . $this->name . DS . $name),
                'title' => $info['title'] ?? '',
                'time' => $info['time'],
                'user' => $info['uname'],
                'delete' => $this->hasPrivOf(Server::AJAX_DELETE) && Sys::user()->hasPriv($info['uid']),
            ];
        }
        echo json_encode($fileList, JSON_UNESCAPED_UNICODE);
    }

    public function actionPost()
    {
        $path = $this->path . DS . $this->name;
        $origName = File::upload($path, null, false, $this->default(FileActions::SIZE_LIMIT));
        $origFile = $path . DS . $origName;
        $name = sha1_file($origFile) . '.' . pathinfo($origName, PATHINFO_EXTENSION);
        $time = Image::getExifDate($origFile);
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
        $file = $path . DS . $name;
        if (file_exists($file)) {
            throw new RuntimeException('Image "' . $name . '" exists.');
        }
        Image::optimizeJpegFile($origFile, $file);
        unlink($origFile);
        $thumb = self::thumbFile($file);
        if ($thumb) {
            $size = $this->default(self::THUMB_SIZE);
            Image::createThumbnail($file, self::thumbFile($file), $size, $size);
        } else {
            throw new RuntimeException("Cannot create thumb for this file.");
        }
        Sys::app()->redirect($this->name);
    }

    public function actionAjaxDelete()
    {
        $data = file_get_contents('php://input');
        $name = basename(parse_url($data, PHP_URL_PATH));
        $files = Meta::loadFileList($this->path . DS . $this->name);
        $uid = $files[$name]['uid'] ?? null;
        if ($uid && Sys::user()->hasPriv($uid)) {
            $file = $this->path . DS . $this->name . DS . $name;
            if (unlink($file) && unlink(self::thumbFile($file))) {
                Msg::info('Succeeded to delete image file "' . $name . '".');
            } else {
                Msg::warn('Failed to delete image file "' . $name . '" or its thumbnail.');
            }
        } else {
            Msg::warn('You do not has previlege to delete image "' . $name . '".');
        }
    }
}

<?php
final class FileActions extends Actions
{
    // folder configs
    public const UPLOAD_TITLE = 'file:uploadTitle';
    public const SIZE_LIMIT = 'file:sizeLimit';
    public const IMAGE_SIZE_LIMIT = 'file::imageSizeLimit';
    public const ACCEPT = 'file:accept';
    public const DICT = 'file::dict';

    public const DEFAULT = [
        self::UPLOAD_TITLE => 'Upload',
        self::SIZE_LIMIT => 65536,
        self::IMAGE_SIZE_LIMIT => 1024 * 1024,
        self::ACCEPT => '.txt,text/plain',
    ];

    public const UPLOAD_ITEM = '_upload';
    public const IMAGES_ITEM = '_images';

    protected function default($confName)
    {
        return parent::default($confName) ?? self::DEFAULT[$confName] ?? null;
    }

    private function getParser($name)
    {
        $path = $this->path;
        $base = $this->base;
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'txt':
                return TextParser::file($path . DS . $name);
            case 'md':
                return MdParser::file($path . DS . $name);
            case 'html':
                return HtmlParser::file($path . DS . $name);
            case 'png':
            case 'jpg':
                return ImageParser::fileUrl($path . DS . $name, $base . $name);
            case 'dict':
                return DictParser::file($path . DS . $name);
            default:
                throw new RuntimeException('Unsupported file type "' . $ext . '".');
        }
    }

    private function buildRibbon()
    {
        $info = Sys::app()->info($this->name);
        if (!$info) {
            return null;
        }
        $formUpdate = null;
        $btnUpdate = null;
        if ($this->hasPrivOf(Server::UPDATE)) {
            $btnUpdate = Icon::EDIT;
            $formUpdate = View::renderHtml('upload', [
                'title' => Icon::EDIT . ' ' . $this->name,
                'action' => '?' . Server::QUERY_UPDATE,
                'accept' => $this->default(self::ACCEPT),
                'sizeLimit' => $this->default(self::SIZE_LIMIT),
            ]);
        }
        $btnDelete = $this->hasPrivOf(Server::AJAX_DELETE) ? Icon::DELETE : null;
        $msg = '';
        if (isset($info['time'])) {
            $msg .= Icon::TIME . '<em>' . Str::timeStr($info['time']) . '</em> ';
        }
        if (isset($info['uname'])) {
            $msg .= Icon::USER . $info['uname'];
        }
        return [
            'msg' => $msg,
            'btnUpdate' => $btnUpdate,
            'btnDelete' => $btnDelete,
            'formUpdate' => $formUpdate,
        ];
    }

    public function actionGet()
    {
        Sys::app()->addScript('js' . DS . 'file');
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
        $this->_title = $parser->title ?? null;
        if (!empty($parser->scripts)) {
            foreach ($parser->scripts as $script) {
                Sys::app()->addScript($script);
            }
        }
        if (!empty($parser->styles)) {
            foreach ($parser->styles as $style) {
                Sys::app()->addStyle($style);
            }
        }
        if (!empty($parser->css)) {
            Sys::app()->addCss($parser->css);
        }
        $ribbon = $this->buildRibbon();
        if ($ribbon) {
            if (!empty($parser->info)) {
                $ribbon['msg'] .= $parser->info;
            }
            View::render('file_ribbon', $ribbon);
        }
        $content = $parser->content;
        $dicts = $this->default(self::DICT);
        if ($parser->applyDict ?? false && $dicts) {
            Arr::makeArray($dicts);
            $words = [];
            foreach ($dicts as $dict) {
                $dictFile = $this->path . DS . $dict . '.dict';
                if (is_file($dictFile)) {
                    $words += DictParser::file($this->path . DS . $dict . '.dict')->dict;
                }
            }
            uksort($words, function ($a, $b) {
                return strlen($b) <=> strlen(($a));
            });
            foreach ($words as $key => $value) {
                $content = preg_replace(
                    '/\s*\b' . preg_quote($key) . '\b\s*/',
                    '<span style="color:magenta">' . $value . '</span>',
                    $content
                );
            }
        }
        echo $content;
    }

    public function actionAjaxDelete()
    {
        $name = $this->name;
        unlink($this->path . DS . $name);
        View::render('deleted', ['name' => $name, 'url' => $this->base]);
    }

    private function getTitle($name)
    {
        if (!empty($_POST['title'])) {
            return $_POST['title'];
        }
        $parser = $this->getParser($name);
        return $parser->title ?? null;
    }

    public function actionPost()
    {
        $name = File::upload($this->path, null, false, $this->default(self::SIZE_LIMIT));
        $user = Sys::user();
        $info = [
            'time' => $_SERVER['REQUEST_TIME'],
            'uid' => $user->id,
            'uname' => $user->name,
        ];
        $title = $this->getTitle($name);
        if ($title) {
            $info['title'] = $title;
        }
        Sys::app()->setInfo($name, $info);
        Sys::app()->redirect($name);
    }

    public function actionPostRaw()
    {
        File::upload($this->path . DS . $this->name, null, false, $this->default(self::SIZE_LIMIT));
        Sys::app()->redirect($this->name);
    }

    public function actionUpdate()
    {
        $name = $this->name;
        try {
            File::upload($this->path, $name, true, $this->default(self::SIZE_LIMIT));
            $title = $this->getTitle($name);
        } catch (RuntimeException $e) {
            if ($e->getCode() === File::NO_FILE_SENT) {
                if (!empty($_POST['title'])) {
                    $title = $_POST['title'];
                } else {
                    throw new RuntimeException("Nothing changed.");
                }
            } else {
                throw $e;
            }
        }
        $info = Sys::app()->info($name);
        if ($title) {
            $info['title'] = $title;
        }
        Sys::app()->setInfo($name, $info);
        Sys::app()->redirect($name);
    }

    public function actionUploadForm()
    {
        View::render('upload', [
            'title' => Icon::UPLOAD . ' ' . $this->default(self::UPLOAD_TITLE),
            'action' => $this->base . $this->name,
            'accept' => $this->default(self::ACCEPT),
            'sizeLimit' => $this->default(self::SIZE_LIMIT),
        ]);
    }

    public function actionImageUploadForm()
    {
        View::render('upload', [
            'title' => Icon::IMAGES . ' Upload image',
            'action' => '',
            'accept' => '.jpg,.jpeg,.png,images/*',
            'sizeLimit' => $this->default(self::IMAGE_SIZE_LIMIT),
        ]);
    }
}

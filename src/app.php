<?php
final class App
{
    private $_home;
    private $_datum = [];
    private $_scripts = [];
    private $_styles = [];
    private $_css = '';
    private $_base;
    private $_path;
    private $_conf;
    private $_name;
    private $_vars;

    public function __construct()
    {
        require_once 'setup.php';
    }

    public function run($extraVars = [])
    {
        list($this->_home, $args, $type) = Server::getHomeAndPath();
        define('PUB_URL', $this->_home . Str::pathUrl(PUB_DIR) . '/');
        session_cache_limiter('nocache');
        session_start();
        $this->_base = $this->_home;
        $this->_path = DATA_PATH;
        $this->_conf = Config::root(CONF_PATH, DATA_PATH);
        $title = null;
        $action = Actions::dir(User::NONE);
        $breadcrumbs = [];
        while (!empty($args)) {
            $name = array_shift($args);
            if ($name == '') {
                continue;
            }
            $action = $this->_conf->action($name, $type);
            if ($action[Actions::ACTION]) {
                break;
            }
            // It is a dir.
            if ($this->_base != $this->_home) {
                $breadcrumbs[] = Html::link($title, $this->_base);
            }
            $this->_path .= DS . $name;
            $this->_base .= $name . '/';
            $title = $this->_conf->title($name);
            if ($this->hasPriv($name, $action[Actions::PRIV])) {
                $this->_conf = $this->_conf->read($name);
            } else {
                $action = Actions::privError($name)->priv();
                break;
            }
        }
        if ($this->_base != $this->_home) {
            $breadcrumbs[] = $title;
        }
        if ($action[Actions::ACTION] === null) {
            // try default item
            $this->_name = $this->_conf->get(Config::DEFAULT_ITEM);
            $action = $this->_conf->action($this->_name, $type);
        } else {
            $this->_name = $name;
        }
        foreach (COMMON_SCRIPTS as $script) {
            $this->addScript($script);
        }
        foreach (COMMON_STYLES as $style) {
            $this->addStyle($style);
        }
        if (!$this->hasPriv($this->_name, $action[Actions::PRIV])) {
            $action = Actions::privError($this->_name)->priv();
        }
        $action[Actions::ACTION]->do($args, $this->_base, $this->_path, $this->_name);
        // This is set after `do`.
        $httpHeaders = $action[Actions::ACTION]->httpHeaders;
        $this->header($httpHeaders);
        if ($type == Server::HEAD) {
            // seems never run to this.
            exit;
        }
        $content = $action[Actions::ACTION]->content;
        if (Server::isAjax()) {
            echo $content;
            exit;
        }
        if ($this->_conf->raw($this->_name)) {
            $title = $action[Actions::ACTION]->title ?? $this->_conf->title($this->_name);
            $this->_vars = [
                'home' => $this->_home,
                'title' => $title,
                'datum' => $this->_datum,
                'scripts' => $this->_scripts,
                'styles' => $this->_styles,
                'css' => $this->_css,
                'base' => $this->_base,
                'content' => $content,
            ] + $extraVars;
            $this->view('raw');
            exit;
        }
        if (is_array(APP_TITLE)) {
            $title = APP_TITLE[Server::lang()] ?? APP_TITLE['en'] ?? 'Lasys';
        } else {
            $title = APP_TITLE;
        }
        $subTitle = $actionDo->title ?? $this->_conf->title($this->_name);
        if ($subTitle) {
            $title .= ' - ' . $subTitle;
        }
        $list = $this->createItemList();
        $this->_vars = [
            'home' => $this->_home,
            'title' => $title,
            'datum' => $this->_datum,
            'scripts' => $this->_scripts,
            'styles' => $this->_styles,
            'css' => $this->_css,
            'base' => $this->_base,
            'breadcrumbs' => $breadcrumbs,
            'buttons' => $list['buttons'],
            'files' => $list['files'],
            'content' => $content,
        ] + $extraVars;
        $this->view('main');
    }

    private function header($httpHeaders)
    {
        if (isset($httpHeaders)) {
            foreach ($httpHeaders as $header) {
                header($header);
            }
        }
    }

    private function makeButton($name, $info)
    {
        $conf = $this->_conf;
        $icon = $conf->meta($name, Config::ICON);
        $title = $conf->meta($name, Config::TITLE);
        return Html::link($icon, $this->_base . $name, $title);
    }

    private function makeItem($name)
    {
        $conf = $this->_conf;
        $isDir = $conf->isDir($name);
        $title = $conf->title($name);
        $desc = $conf->meta($name, Config::DESC);
        if (!$isDir && !isset($desc)) {
            $desc = $conf->meta($name, 'uname') ?? '';
            $time = $conf->meta($name, 'time');
            if (isset($time)) {
                $desc .= ' @ ' . Str::timeStr($time);
            }
        }
        $li = ($name === $this->_name) ? '<li class="highlighted">' : '<li>';
        $li .= Html::link($title, $this->_base . $name . ($isDir ? '/' : ''), $desc);
        $li .= $isDir ? Icon::FOLDER : '';
        $li .= '</li>';
        return [
            'isDir' => $isDir,
            'title' => $title,
            'time' =>  $info['time'] ?? 0,
            'li' => $li,
        ];
    }

    private function createItemList()
    {
        $conf = $this->_conf;
        $buttons = [];
        $list = [];
        if ($this->_base != $this->_home) {
            $buttons[] = Html::link(Icon::UPPER_LEVEL, dirname($this->_base), 'Upper Level');
        }
        foreach ($conf->list() as $name => $info) {
            if ($conf->hidden($name) || !$this->hasPrivOf($name, Server::GET)) {
                continue;
            }
            if (isset($info[Config::TYPE]) && $info[Config::TYPE] == Config::BTN) {
                $buttons[] = $this->makeButton($name, $info);
            } else {
                $list[] = $this->makeItem($name);
            }
        }
        $order = $conf->get(Config::ORDER);
        if ($order) {
            usort($list, $order);
        }
        return ['buttons' => $buttons, 'files' => array_column($list, 'li')];
    }

    public function hasPriv($name, $priv, $uid = null)
    {
        if ($priv === null) {
            return false;
        }
        if ($uid == null) {
            $uid = $this->_conf->info($name)['uid'] ?? null;
        }
        return Sys::user()->hasPriv($priv, $uid);
    }

    public function hasPrivOf($name, $type, $uid = null)
    {
        $actions = $this->_conf->action($name, $type);
        if (isset($actions[Actions::PRIV])) {
            return $this->hasPriv($name, $actions[Actions::PRIV], $uid);
        }
        return false;
    }

    public function conf()
    {
        return $this->_conf;
    }

    public function view($view)
    {
        View::render($view, $this->_vars);
    }

    public function addScript($file)
    {
        if (
            !$this->tryAddScript($file . '.js')
            && !$this->tryAddScript('sys' . DS . $file . '.js')
        ) {
            $this->_scripts[] = $file;
        }
    }

    private function tryAddScript($file)
    {
        if (is_file(PUB_PATH . DS . $file)) {
            $this->_scripts[] = PUB_URL . Str::pathUrl($file) . '?v=' . ASSET_SUM;
            return true;
        }
        return false;
    }

    public function addStyle($file)
    {
        if (
            !$this->tryAddStyle($file . '.css')
            && !$this->tryAddStyle('sys' . DS . $file . '.css')
        ) {
            $this->_styles[] = $file;
        }
    }

    private function tryAddStyle($file)
    {
        if (is_file(PUB_PATH . DS . $file)) {
            $this->_styles[] = PUB_URL . Str::pathUrl($file) . '?v=' . ASSET_SUM;
            return true;
        }
        return false;
    }

    public function addData($name, $data, $flags = JSON_UNESCAPED_UNICODE)
    {
        $this->_datum[] = 'const ' . $name . ' = ' . json_encode($data, $flags) . ';';
    }

    public function addCss($css)
    {
        $this->_css .= $css;
    }

    public function redirect($name)
    {
        $url = (!str_contains($name, '//')) ? $this->_base . $name : $name;
        header('Location: ' . $url);
        exit;
    }

    public function home()
    {
        return $this->_home;
    }
}

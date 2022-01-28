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
    private $_files;
    private $_vars;

    public function __construct()
    {
        define('CONF_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'configs');
        require_once CONF_PATH . DIRECTORY_SEPARATOR . 'defs.php';
        // DS is not define till here.
        require_once 'setup.php';
    }

    public function run()
    {
        list($this->_home, $args, $type) = Server::getHomeAndPath();
        define('PUB_URL', $this->_home . Str::pathUrl(PUB_DIR) . '/');
        session_cache_limiter('nocache');
        session_start();
        $this->_base = $this->_home;
        $this->_path = DATA_PATH;
        $this->_conf = new Config(CONF_PATH);
        $title = null;
        $action = Actions::noop();
        $breadcrumbs = [];
        while (!empty($args)) {
            $name = array_shift($args);
            if ($name == '') {
                continue;
            }
            $action = $this->action($name, $type);
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
                $this->_conf->shift($name);
            } else {
                $action = Actions::error('You do not have privilege to access "' . $name . '".');
                break;
            }
        }
        if ($this->_base != $this->_home) {
            $breadcrumbs[] = $title;
        }
        if ($action[Actions::ACTION] === null) {
            $this->_name = $this->_conf->get(Config::DEFAULT_ITEM);
            $action = $this->action($this->_name, $type);
        } else {
            $this->_name = $name;
        }
        $httpHeaders = $action[Actions::ACTION]->httpHeaders;
        if ($type == Server::HEAD) {
            // Seems never run to this.
            $this->header($httpHeaders);
            exit;
        }
        if ($this->_conf->get(Config::READ_ONLY)) {
            $this->_files = [];
        } else {
            // This is needed when calling action->do, e.g., for file uploading.
            $this->_files = Meta::loadFileList($this->_path, function ($file) {
                return $this->_conf->excluded($file);
            });
        }
        $this->addScript('js' . DS . 'main');
        $this->addStyle('css' . DS . 'main');
        $this->addStyle('lib' . DS . 'bootstrap-icons');
        $actionDo = $action[Actions::ACTION];
        if ($this->hasPriv($this->_name, $action[Actions::PRIV])) {
            $actionDo->do($args, $this->_base, $this->_path, $this->_name);
        } else {
            $actionDo->doError('You do not have privilege to do this.');
        }
        $content = $actionDo->content;
        if (Server::isAjax($type)) {
            // This is needed for CORS post, put and delete.
            echo $content;
            exit;
        }
        $title = APP_TITLE;
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
        ];
        $this->header($httpHeaders);
        $this->view('main');
    }

    private function action($name, $type)
    {
        $conf = $this->_conf;
        $action = $conf->action($name, $type);
        if ($action) {
            return $action;
        }
        $file = $this->_path . DS . $name;
        if (is_dir($file)) {
            return Actions::noop();
        }
        return $conf->get(Config::ETC)[$type] ?? Actions::default()->priv();
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
        return Html::link($info[Config::BUTTON], $this->_base . $name, $info[Config::TITLE] ?? Str::captalize($name));
    }

    private function makeItem($name, $info)
    {
        $title = $info[Config::TITLE] ?? Str::captalize(pathinfo($name, PATHINFO_FILENAME));
        $li = ($name === $this->_name) ? '<li class="highlighted">' : '<li>';
        $li .= Html::link($title, $this->_base . $name . ($info['isDir'] ? '/' : ''));
        $li .= $info['isDir'] ? Icon::FOLDER : '';
        $li .= '</li>';
        return [
            'name' => $title,
            'time' => $info['time'] ?? 0,
            'li' => $li,
        ];
    }

    private function createItemList()
    {
        $conf = $this->_conf;
        $files = $this->_files;
        $list0 = [];
        $buttons = [];
        if ($this->_base != $this->_home) {
            $buttons[] = Html::link(Icon::UPPER_LEVEL, dirname($this->_base), 'Upper Level');
        }
        foreach ($conf->list() as $name => $info) {
            if ($conf->hidden($name) || !$this->hasPrivOf($name, Server::GET)) {
                continue;
            }
            if (isset($info[Config::BUTTON])) {
                $buttons[] = $this->makeButton($name, $info);
            } else {
                $info['isDir'] = $this->isDir($name);
                $list0[] = $this->makeItem($name, $info);
            }
        }
        $list1 = [];
        foreach ($files as $name => $file) {
            $list1[] = $this->makeItem($name, $file);
        }
        $order = $conf->get(FileActions::ORDER);
        if ($order) {
            usort($list1, $order);
        }
        return ['buttons' => $buttons, 'files' => array_column(array_merge($list0, $list1), 'li')];
    }

    private function isDir($name)
    {
        return $this->action($name, Server::GET)[Actions::ACTION] === null;
    }

    public function hasPriv($name, $privs, $uid = null)
    {
        if ($uid == null) {
            $uid = $this->info($name)['uid'] ?? null;
        }
        return Sys::user()->hasPrivs($privs, $uid);
    }

    public function hasPrivOf($name, $type, $uid = null)
    {
        $actions = $this->action($name, $type);
        if ($actions) {
            return $this->hasPriv($name, $actions[Actions::PRIV], $uid);
        }
        return false;
    }

    public function conf($name)
    {
        return $this->_conf->get($name);
    }

    public function info($name)
    {
        return $this->_files[$name] ?? null;
    }

    public function setInfo($name, $info)
    {
        $this->_files[$name] = $info;
        Meta::save($this->_path, $this->_files);
    }

    public function view($view, $extraVars = [])
    {
        View::render($view, array_merge($this->_vars, $extraVars));
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
            $this->_scripts[] = PUB_URL . Str::pathUrl($file);
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
            $this->_styles[] = PUB_URL . Str::pathUrl($file);
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
        $url = (strpos($name, '//') === false) ? $this->_base . $name : $name;
        header('Location: ' . $url);
        exit;
    }
}

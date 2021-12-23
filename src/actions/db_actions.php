<?php
final class DbActions extends Actions
{
    public const SCRIPT = 'script';

    private function buildField($columns)
    {
        $fields = [];
        foreach ($columns as $c) {
            if ($c['Extra'] == 'auto_increment') {
                continue;
            }
            $required = ($c['Null'] !== 'YES' && !isset($c['Default']));
            $name = $c['Field'];
            $label = $name;
            $type = 'text';
            $attrs = [];
            $fields[$name] = compact('label', 'type', 'required', 'attrs');
        }
        return $fields;
    }

    private function buildMeta()
    {
        $buttons = [];
        $editForm = null;
        if ($this->hasPriv(Server::PUT)) {
            $columns = Sys::db()->getColumns($this->name);
            $fields = $this->buildField($columns);
            if (!empty($fields)) {
                $buttons[] = '<span id="-meta-btn-edit-">' . Icon::INSERT . '</span>';
                $editForm = View::renderHtml('db_edit', [
                    'title' => Icon::INSERT . ' ' . $this->name,
                    'action' => '?' . Server::KEY . '=' . Server::PUT,
                    'fields' => $this->buildField($columns),
                ]);
            }
        }
        return ['buttons' => $buttons, 'editForm' => $editForm];
    }

    private function doView($msg = null)
    {
        $script = $this->attr(self::SCRIPT);
        if (isset($script)) {
            Sys::app()->addScript($script);
        }
        Sys::app()->addScript('js' . DS . 'db_table');
        $meta = $this->buildMeta();
        $meta['time'] = Sys::db()->getLastModTime($this->name);
        $meta['msg'] = $msg;
        View::render('meta', $meta);
    }

    public function actionGet()
    {
        $this->doView();
    }

    public function actionAjaxGet()
    {
        $sql = 'select * from ' . $this->name;
        $data = Sys::db()->getAll($sql);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function actionPut()
    {
        $row = Sys::db()->insert($this->name, $_POST);
        $this->doView('Succeeded to insert ' . $row . ' records.');
    }

    public function actionDump()
    {
        Sys::db()->dump($this->path);
        echo '<p class="sys center">Dumping succeed!</p>';
    }
}

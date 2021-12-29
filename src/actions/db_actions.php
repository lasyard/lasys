<?php
final class DbActions extends Actions
{
    // configs
    public const SCRIPT = 'db:script';
    public const LABELS = 'db:labels';

    private function getLabel($name)
    {
        $labels = $this->conf(self::LABELS);
        return ($labels && array_key_exists($name, $labels)) ? $labels[$name] : ucfirst($name);
    }

    private function buildFields($columns)
    {
        $fields = [];
        foreach ($columns as $c) {
            $name = $c['Field'];
            $primary = ($c['Key'] == 'PRI');
            $auto = ($c['Extra'] == 'auto_increment');
            $required = ($c['Null'] !== 'YES' && !isset($c['Default']));
            $label = $this->getLabel($name);
            $type = 'text';
            $attrs = [];
            $fields[$name] = compact('label', 'type', 'primary', 'auto', 'required', 'attrs');
        }
        return $fields;
    }

    private function buildMeta($fields)
    {
        $editForm = null;
        $btnEdit = null;
        if ($this->hasPrivOf(Server::POST_UPDATE)) {
            $btnEdit = Icon::INSERT;
            $editForm = View::renderHtml('db_edit', [
                'title' => Icon::INSERT . ' ' . $this->name,
                'fields' => $fields,
                'attrs' => [
                    'name' => '-form-db-insert-',
                ],
                'purpose' => 'insert',
            ]);
        }
        $time = Sys::db()->getLastModTime($this->name);
        $msg = Icon::TIME . '<em>' . date('Y.m.d H:i:s', $time) . '</em>';
        return ['msg' => $msg, 'btnEdit' => $btnEdit, 'editForm' => $editForm];
    }

    public function actionGet()
    {
        $script = $this->conf(self::SCRIPT);
        if (isset($script)) {
            Sys::app()->addScript($script);
        }
        Sys::app()->addScript('js' . DS . 'db_table');
        $columns = Sys::db()->getColumns($this->name);
        $fields = $this->buildFields($columns);
        $keyFields = array_keys(array_filter($fields, function ($f) {
            return $f['primary'];
        }));
        Sys::app()->addData('TABLE_KEY_FIELDS', $keyFields);
        $meta = $this->buildMeta($fields);
        View::render('meta', $meta);
        if ($this->hasPrivOf(Server::AJAX_PUT)) {
            View::render('db_edit', [
                'title' => Icon::EDIT . ' ' . $this->name,
                'fields' => $fields,
                'attrs' => [
                    'name' => '-form-db-update-',
                    'style' => 'display:none',
                ],
                'purpose' => 'update',
            ]);
        }
    }

    public function actionAjaxGet()
    {
        $sql = 'select * from ' . $this->name;
        $result = Sys::db()->getDataSet($sql);
        $result['canEdit'] = $this->hasPrivOf(Server::AJAX_PUT);
        $result['canDelete'] = $this->hasPrivOf(Server::AJAX_DELETE);
        $labels = [];
        foreach ($result['columns'] as $name => $index) {
            $labels[$index] = $this->getLabel($name);
        }
        $result['labels'] = $labels;
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function actionAjaxPut()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $row = Sys::db()->update($this->name, $data['keys'], $data['data']);
        echo 'Succeeded to update ', $row, ' records.';
    }

    // This is not used because of ajaxfy.
    public function actionPostUpdate()
    {
        $name = $this->name;
        Sys::db()->insert($name, $_POST);
        // Do redirect to remove the 'update' query key.
        Sys::app()->redirect($name);
    }

    public function actionAjaxPost()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $row = Sys::db()->insert($this->name, $data);
        echo 'Succeeded to insert ', $row, ' records.';
    }

    public function actionAjaxDelete()
    {
        $row = Sys::db()->delete($this->name, $_GET);
        echo Icon::INFO, 'Succeeded to delete ', $row, ' records.';
    }

    public function actionDump()
    {
        Sys::db()->dump($this->path);
        echo '<p class="sys center">Dumping succeed!</p>';
    }

    public static function accessDb($script, $labels = [])
    {
        return function ($item) use ($script, $labels) {
            $item[Server::GET] = DbActions::get();
            $item[Server::AJAX_GET] = DbActions::ajaxGet();
            $item[Server::AJAX_PUT] = DbActions::ajaxPut();
            $item[Server::POST_UPDATE] = DbActions::postUpdate();
            $item[Server::AJAX_POST] = DbActions::ajaxPost();
            $item[Server::AJAX_DELETE] = DbActions::ajaxDelete();
            $item[DbActions::SCRIPT] = $script;
            $item[DbActions::LABELS] = $labels;
            return $item;
        };
    }
}

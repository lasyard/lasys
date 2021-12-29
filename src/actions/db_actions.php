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

    private function buildField($columns, &$keyColumns)
    {
        $fields = [];
        foreach ($columns as $c) {
            $name = $c['Field'];
            if ($c['Key'] == 'PRI') {
                $keyColumns[] = $name;
            }
            if ($c['Extra'] == 'auto_increment') {
                continue;
            }
            $required = ($c['Null'] !== 'YES' && !isset($c['Default']));
            $label = $this->getLabel($name);
            $type = 'text';
            $attrs = [];
            $fields[$name] = compact('label', 'type', 'required', 'attrs');
        }
        return $fields;
    }

    private function buildMeta()
    {
        $editForm = null;
        $btnEdit = null;
        if ($this->hasPrivOf(Server::POST_UPDATE)) {
            $btnEdit = Icon::INSERT;
            $columns = Sys::db()->getColumns($this->name);
            $keyColumns = [];
            $fields = $this->buildField($columns, $keyColumns);
            Sys::app()->addData('TABLE_KEY_COLUMNS', $keyColumns);
            $editForm = View::renderHtml('db_edit', [
                'title' => Icon::INSERT . ' ' . $this->name,
                'action' => Server::QUERY_POST_UPDATE,
                'fields' => $fields,
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
        $meta = $this->buildMeta();
        View::render('meta', $meta);
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

    public function actionPostUpdate()
    {
        $name = $this->name;
        Sys::db()->insert($name, $_POST);
        // Do redirect to remove the 'update' query key.
        Sys::app()->redirect($name);
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
            $item[Server::POST_UPDATE] = DbActions::postUpdate();
            $item[Server::AJAX_DELETE] = DbActions::ajaxDelete();
            $item[DbActions::SCRIPT] = $script;
            $item[DbActions::LABELS] = $labels;
            return $item;
        };
    }
}

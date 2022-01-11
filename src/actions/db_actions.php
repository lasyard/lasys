<?php
final class DbActions extends Actions
{
    // configs
    public const SCRIPT = 'db:script';
    public const LABELS = 'db:labels';
    public const READ_ONLY = 'db:readOnly';
    public const INSERT_FORM = 'db:insertForm';

    private function getLabel($name)
    {
        $labels = $this->conf(self::LABELS);
        return ($labels && array_key_exists($name, $labels)) ? $labels[$name] : ucfirst($name);
    }

    private function getReadOnly($name)
    {
        $ro = $this->conf(self::READ_ONLY);
        return $ro && in_array($name, $ro);
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
            $readOnly = $this->getReadOnly($name);
            $attrs = [];
            switch ($c['Type']) {
                case 'text':
                    $type = 'textarea';
                    $attrs['rows'] = 4;
                    break;
                case 'year':
                    $type = 'select';
                    $attrs['options'] = range(date('Y'), 1970, -1);
                    break;
                case 'datetime':
                    $type = 'datetime-local';
                    break;
                case 'int':
                case 'bigint':
                    $type = 'number';
                    break;
                case 'tinyint(1)':
                    if (in_array('bool', explode(',', $c['Comment']))) {
                        $type = 'checkbox';
                    } else {
                        $type = 'number';
                    }
                    break;
                default:
                    if (preg_match('/enum\((.*)\)/', $c['Type'], $matches)) {
                        $type = 'select';
                        $attrs['options'] = str_getcsv($matches[1], ',', "'");
                    } else {
                        $type = 'text';
                    }
                    break;
            }
            $fields[$name] = compact('label', 'type', 'primary', 'auto', 'required', 'readOnly', 'attrs');
        }
        return $fields;
    }

    private function buildMeta($fields)
    {
        $editForm = null;
        $btnEdit = null;
        if ($this->hasPrivOf(Server::POST_UPDATE)) {
            $btnEdit = Icon::INSERT;
            $formView = $this->conf(self::INSERT_FORM) ?? 'db_edit';
            $editForm = View::renderHtml($formView, [
                'title' => Icon::INSERT . ' ' . $this->name,
                'fields' => $fields,
                'name' => '-form-db-insert',
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
        Sys::app()->addData('_TABLE_FIELDS', array_map(function ($v) {
            return [
                'primary' => $v['primary'],
                'auto' => $v['auto']
            ];
        }, $fields));
        Sys::app()->addData('_TABLE_CAN_DELETE', $this->hasPrivOf(Server::AJAX_DELETE));
        $meta = $this->buildMeta($fields);
        View::render('meta', $meta);
        if ($this->hasPrivOf(Server::AJAX_PUT)) {
            View::render('db_edit', [
                'title' => Icon::EDIT . ' ' . $this->name,
                'fields' => $fields,
                'name' => '-form-db-update',
                'attrs' => [
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
        self::echoInfo('Succeeded to update ' . $row . ' records.');
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
        self::echoInfo('Succeeded to insert ' . $row . ' records.');
    }

    public function actionAjaxDelete()
    {
        $row = Sys::db()->delete($this->name, $_GET);
        self::echoInfo('Succeeded to delete ' . $row . ' records.');
    }

    public function actionDump()
    {
        Sys::db()->dump($this->path);
        echo '<p class="sys center">Dumping succeed!</p>';
    }

    public static function echoInfo($msg)
    {
        echo '<p class="center">', Icon::INFO, ' ', $msg, '</p>', PHP_EOL;
    }

    public static function echoHotInfo($msg)
    {
        echo '<p class="hot center">', Icon::WARN, ' ', $msg, '</p>', PHP_EOL;
    }
}

<?php
class DbActions extends Actions
{
    // configs
    public const TABLE = 'db:table';
    public const SCRIPT = 'db:script';
    public const LABELS = 'db:labels';
    public const INSERT_FORM = 'db:insertForm';

    private function getLabel($name)
    {
        $labels = $this->conf(self::LABELS);
        return ($labels && array_key_exists($name, $labels)) ? $labels[$name] : ucfirst($name);
    }

    private function getTable()
    {
        return $this->conf(self::TABLE) ?? $this->name;
    }

    private function buildFields($columns)
    {
        $fields = [];
        foreach ($columns as $c) {
            $name = $c['Field'];
            $comments = explode(',', $c['Comment']);
            $primary = ($c['Key'] == 'PRI');
            $auto = ($c['Extra'] == 'auto_increment') || in_array('auto', $comments);
            $required = ($c['Null'] !== 'YES' && !isset($c['Default']));
            $readOnly = in_array('readOnly', $comments);
            $label = $this->getLabel($name);
            $attrs = [];
            $i = strpos($c['Type'], '(');
            $type = ($i === false) ?  $c['Type'] : substr($c['Type'], 0, $i);
            switch ($type) {
                case 'text':
                    $type = 'textarea';
                    $attrs['rows'] = 4;
                    break;
                case 'year':
                    $type = 'select';
                    $attrs['options'] = range(date('Y'), 1970, -1);
                    break;
                case 'date':
                    $type = 'date';
                    break;
                case 'datetime':
                    $type = 'datetime-local';
                    break;
                case 'int':
                case 'bigint':
                    $type = 'number';
                    break;
                case 'tinyint':
                    if (in_array('bool', $comments)) {
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

    private function buildRibbon($fields)
    {
        $formInsert = null;
        $btnInsert = null;
        if ($this->hasPrivOf(Server::AJAX_POST)) {
            $btnInsert = Icon::INSERT;
            $formView = $this->conf(self::INSERT_FORM) ?? 'db_edit';
            $formInsert = View::renderHtml($formView, [
                'title' => Icon::INSERT . ' ' . $this->name,
                'fields' => $fields,
                'name' => '-form-insert',
                'purpose' => 'insert',
            ]);
        }
        $time = Sys::db()->getLastModTime($this->getTable());
        $msg = Icon::TIME . '<em>' . Str::timeStr($time) . '</em>';
        return ['msg' => $msg, 'btnInsert' => $btnInsert, 'formInsert' => $formInsert];
    }

    public function actionGet()
    {
        $script = $this->conf(self::SCRIPT);
        if (isset($script)) {
            Arr::forOneOrMany($script, function ($s) {
                Sys::app()->addScript($s);
            });
        }
        Sys::app()->addScript('js' . DS . 'db');
        $columns = Sys::db()->getColumns($this->getTable());
        $fields = $this->buildFields($columns);
        Sys::app()->addData('_TABLE_FIELDS', array_map(function ($v) {
            return [
                'primary' => $v['primary'],
                'auto' => $v['auto']
            ];
        }, $fields));
        Sys::app()->addData('_TABLE_CAN_DELETE', $this->hasPrivOf(Server::AJAX_DELETE));
        $ribbon = $this->buildRibbon($fields);
        View::render('db_ribbon', $ribbon);
        if ($this->hasPrivOf(Server::AJAX_UPDATE)) {
            View::render('db_edit', [
                'title' => Icon::EDIT . ' ' . $this->name,
                'fields' => $fields,
                'name' => '-form-update',
                'attrs' => [
                    'style' => 'display:none',
                ],
                'purpose' => 'update',
            ]);
        }
    }

    public function actionAjaxGet()
    {
        $sql = 'select * from ' . $this->getTable();
        return $this->actionAjaxGetCustomized($sql);
    }

    public function actionAjaxGetCustomized($sql, $trans = null)
    {
        $result = Sys::db()->getDataSet($sql);
        $labels = [];
        foreach ($result['columns'] as $name => $index) {
            $labels[$index] = $this->getLabel($name);
        }
        $result['labels'] = $labels;
        if ($trans) {
            $trans($result);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function actionAjaxUpdate($trans = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($trans) {
            $trans($data['keys'], $data['data']);
        }
        $row = Sys::db()->update($this->getTable(), $data['keys'], $data['data']);
        Msg::info('Succeeded to update ' . $row . ' records.');
    }

    // This is not used because of ajaxfy.
    public function actionUpdate()
    {
        Sys::db()->insert($this->getTable(), $_POST);
        // Do redirect to remove the 'update' query key.
        Sys::app()->redirect($this->name);
    }

    public function actionAjaxPost($trans = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($trans) {
            $trans($data);
        }
        $row = Sys::db()->insert($this->getTable(), $data);
        Msg::info('Succeeded to insert ' . $row . ' records.');
    }

    public function actionAjaxDelete($trans = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($trans) {
            $trans($data);
        }
        $row = Sys::db()->delete($this->getTable(), $data);
        Msg::info('Succeeded to delete ' . $row . ' records.');
    }

    public function actionDump()
    {
        Sys::db()->dump($this->path . DS . '_dump');
        echo '<p class="sys center">Dumping succeed!</p>';
    }
}

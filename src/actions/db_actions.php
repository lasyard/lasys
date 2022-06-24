<?php
class DbActions extends Actions
{
    // configs
    public const TABLE = 'db:table';
    public const LABELS = 'db:labels';
    public const INSERT_FORM = 'db:insertForm';

    protected function getLabel($name)
    {
        $labels = $this->conf(self::LABELS);
        $labels1 = Sys::app()->conf(self::LABELS);
        return $labels[$name] ?? $labels1[$name] ?? ucfirst($name);
    }

    protected function getTable()
    {
        return $this->conf(self::TABLE) ?? $this->name;
    }

    protected function buildFields()
    {
        $columns = Sys::db()->getColumns($this->getTable());
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
        /* Time consuming operation in some env. Remove it.
        $time = Sys::db()->getLastModTime($this->getTable());
        $msg = Icon::TIME . '<em>' . Str::timeStr($time) . '</em>';
        */
        $msg = $this->default('title') ?? '&nbsp;';
        return ['msg' => $msg, 'btnInsert' => $btnInsert, 'formInsert' => $formInsert];
    }

    public function actionGet($fields = null, $pre = null)
    {
        $fields ??= $this->buildFields();
        $this->configScriptsAndStyles();
        Sys::app()->addScript('js' . DS . 'db');
        Sys::app()->addData('_TABLE_FIELDS', array_map(function ($v) {
            return [
                'primary' => $v['primary'] ?? false,
                'auto' => $v['auto'] ?? false,
            ];
        }, $fields));
        Sys::app()->addData('_TABLE_CAN_DELETE', $this->hasPrivOf(Server::AJAX_DELETE));
        $ribbon = $this->buildRibbon($fields);
        View::render('db_ribbon', $ribbon);
        if ($pre) {
            $pre();
        }
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

    public function actionAjaxGetCustomized($sql, $post = null, ...$paras)
    {
        $result = Sys::db()->getDataSet($sql, ...$paras);
        $labels = [];
        foreach ($result['columns'] as $name => $index) {
            $labels[$index] = $this->getLabel($name);
        }
        $result['labels'] = $labels;
        if ($post) {
            $post($result);
        }
        $this->_httpHeaders[] = 'Content-Type: application/json';
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * $table: name of the relation table
     * $kv: key-value pairs for relation table
     * $cols: column names in relation table other than key
     * $values: values for columns in relation table other than key
     */
    public static function updateRelation($table, $kv, $cols = [], $values = [])
    {
        $rows1 = Sys::db()->delete($table, $kv);
        if (!empty($values)) {
            $cols = array_merge(Arr::toArray($cols), array_keys($kv));
            $vKey = array_values($kv);
            $rows2 = Sys::db()->insertBatch($table, $cols, array_map(function ($v) use ($vKey) {
                return array_merge(Arr::toArray($v), $vKey);
            }, $values));
            Msg::info('Replaced ' . $rows1 . ' records with ' . $rows2 . ' records in table \"' . $table . '\".');
        } else {
            Msg::info('Deleted ' . $rows1 . ' records in table \"' . $table . '\".');
        }
    }

    public function actionAjaxUpdate($pre = null, $post = null, $trans = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $ctx = $pre ? $pre($data['keys'], $data['data']) : null;
        Sys::db()->beginTransaction();
        $rows = Sys::db()->update($this->getTable(), $data['keys'], $data['data'], $trans);
        Msg::info('Succeeded to update ' . $rows . ' records.');
        if ($post) {
            $post($ctx, $rows);
        }
    }

    // This is not used because of ajaxfy.
    public function actionUpdate()
    {
        Sys::db()->insert($this->getTable(), $_POST);
        // Do redirect to remove the 'update' query key.
        Sys::app()->redirect($this->name);
    }

    public function actionAjaxPost($pre = null, $post = null, $trans = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $ctx = $pre ? $pre($data) : $data;
        Sys::db()->beginTransaction();
        list($rows, $id) = Sys::db()->insert($this->getTable(), $data, $trans);
        Msg::info('Succeeded to insert ' . $rows . ' records.');
        if ($post) {
            $post($ctx, $rows, $id);
        }
    }

    public function actionAjaxDelete($pre = null, $post = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $ctx = $pre ? $pre($data) : $data;
        Sys::db()->beginTransaction();
        $rows = Sys::db()->delete($this->getTable(), $data);
        Msg::info('Succeeded to delete ' . $rows . ' records.');
        if ($post) {
            $post($ctx);
        }
    }

    public function actionDump()
    {
        Sys::db()->dump($this->path . DS . '_dump');
        echo '<p class="sys center">Dumping succeed!</p>';
    }

    public static function splitIdsFun($col)
    {
        return function (&$r) use ($col) {
            $ci = $r['columns'][$col];
            foreach ($r['data'] as &$d) {
                $c = &$d[$ci];
                $c = $c != null ? array_map('intval', explode(',', $c)) : [];
            }
        };
    }

    public static function getColFun($col)
    {
        return function ($d) use ($col) {
            return $d[$col];
        };
    }
}

<?php
final class DbActions extends Actions
{
    public const DATA_PANEL_ID = '__data__';

    public function actionGet($script)
    {
        Sys::app()->addScript($script);
        Sys::app()->addScript('js' . DS . 'db_table');
    }

    public function actionAjaxGet()
    {
        $sql = 'select * from ' . $this->name;
        $data = Sys::db()->getAll($sql);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function actionDump()
    {
        Sys::db()->dump($this->path);
        echo '<p class="sys center">Dumping succeed!</p>';
    }
}

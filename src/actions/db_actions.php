<?php
final class DbActions extends Actions
{
    public const DATA_PANEL_ID = '__data__';

    private function buildButtons()
    {
        $buttons = [];
        $editForm = null;
        return [$buttons, $editForm];
    }
    public function actionGet($script)
    {
        Sys::app()->addScript($script);
        Sys::app()->addScript('js' . DS . 'db_table');
        list($buttons, $editForm) = $this->buildButtons();
        View::render('meta', [
            'time' => Sys::db()->getLastModTime($this->name),
            'buttons' => $buttons,
            'editForm' => $editForm,
        ]);
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

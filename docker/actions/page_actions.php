<?php
final class PageActions extends Actions
{
    public function actionIndex()
    {
        Sys::app()->redirect('README.md');
    }
}

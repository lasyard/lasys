<?php
final class UserActions extends Actions
{
    public function actionShowUser()
    {
        echo '<p class="center sys">', 'Logged in as "', Sys::user()->name, '".</p>';
    }

    public function actionLoginForm()
    {
        if (Sys::user()->isGuest) {
            Sys::app()->addScript('js/login');
            View::render('login');
            return;
        }
        $this->actionShowUser();
    }

    public function actionLogin()
    {
        if (isset($_POST['name'])) {
            Sys::user()->login($_POST['name'], $_POST['password']);
        }
        $this->actionShowUser();
    }

    public function actionLogout()
    {
        if (!Sys::user()->isGuest) {
            $name = Sys::user()->name;
            Sys::user()->logout();
            echo '<p class="center sys">User "', $name, '" logged out successfully.</p>';
        } else {
            echo '<p class="center sys">No user has been logged in.</p>';
        }
    }
}

<?php
final class User
{
    use Getter;

    const GUEST = 'Guest';

    public const ADMIN = 'admin';
    public const OWNER = 'owner';
    public const READ0 = 'read0';
    public const EDIT = 'edit';
    public const EDIT0 = 'edit0';

    private $_user;

    public function __construct()
    {
        if (isset($_SESSION['user'])) {
            $this->_user = $_SESSION['user'];
        }
        if ($this->_user == null) {
            if (isset($_COOKIE['id']) && isset($_COOKIE['password'])) {
                if (!$this->check($_COOKIE['id'], $_COOKIE['password'])) {
                    throw new RuntimeException('Wrong user name or password!');
                }
            } else if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                $this->check($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], false);
            }
        }
    }

    public function login($name, $password)
    {
        $this->logout();
        $this->check($name, $password);
    }

    public function logout()
    {
        $this->_user = null;
        if (isset($_SESSION['user'])) {
            session_unset();
            // Cause 'Segmentation fault' in Apache 2.4/PHP 8.1 using Chrome cached, don't know why.
            // setcookie('PHPSESSID', '', 0, '/');
            setcookie('id', '', 0, '/');
            setcookie('password', '', 0, '/');
        }
    }

    private function check($id, $password, $session = true)
    {
        $db = Sys::db();
        $user = $db->getOne('select * from `users` where `id` = ?', $id);
        if ($user) {
            $hash = $user['password_hash'];
            if (hash_equals($hash, crypt($password, $hash))) {
                $userInfo = [
                    'id' => $id,
                    'name' => $user['name'],
                    'priv' => $user['priv'] ? explode(',', $user['priv']) : [],
                ];
                $this->_user = $userInfo;
                if ($session) {
                    $_SESSION['user'] = array(
                        'id' => $id,
                        'name' => $user['name'],
                        'priv' => $user['priv'] ? explode(',', $user['priv']) : [],
                    );
                    $options = [
                        'expires' => time() + 60 * 60 * 24 * 30,
                        'path' => '/',
                        'samesite' => 'Strict',
                    ];
                    setcookie('id', $id, $options);
                    setcookie('password', $password, $options);
                }
                return true;
            }
        }
        return false;
    }

    public function id()
    {
        return $this->_user ? $this->_user['id'] : null;
    }

    public function name()
    {
        return $this->_user ? $this->_user['name'] : self::GUEST;
    }

    public function isGuest()
    {
        return $this->_user == null;
    }

    public function hasPriv($priv)
    {
        if (empty($priv)) {
            return true;
        }
        if ($this->_user == null) {
            return false;
        }
        $id = $this->user['id'];
        if ($id === $priv || $id === User::ADMIN) {
            return true;
        }
        $privs = $this->_user['priv'];
        return in_array($priv, $privs);
    }

    public function hasPrivs($privs, $uid)
    {
        foreach ($privs as $priv) {
            if ($priv === User::OWNER) {
                if (isset($uid) && $this->hasPriv($uid)) {
                    continue;
                }
                return false;
            }
            if (!$this->hasPriv($priv)) {
                return false;
            }
        }
        return true;
    }
}

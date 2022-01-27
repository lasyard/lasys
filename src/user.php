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
        } else if (isset($_COOKIE['id']) && isset($_COOKIE['password'])) {
            $this->check($_COOKIE['id'], $_COOKIE['password']);
        } else {
            $this->_user = null;
        }
    }

    public function login($name, $password)
    {
        $this->logout();
        $this->check($name, $password);
    }

    public function logout()
    {
        if (isset($_SESSION['user'])) {
            $this->_user = null;
            session_unset();
            // Cause 'Segmentation fault' in Apache 2.4/PHP 8.1 using Chrome cached, don't know why.
            // setcookie('PHPSESSID', '', 0, '/');
            setcookie('id', '', 0, '/');
            setcookie('password', '', 0, '/');
        }
    }

    private function check($id, $password)
    {
        $db = Sys::db();
        $user = $db->getOne('select * from `users` where `id` = ?', $id);
        if ($user) {
            $hash = $user['password_hash'];
            if (hash_equals($hash, crypt($password, $hash))) {
                $_SESSION['user'] = array(
                    'id' => $id,
                    'name' => $user['name'],
                    'priv' => explode(',', $user['priv']),
                );
                $expire = time() + 60 * 60 * 24 * 30;
                setcookie('id', $id, $expire, '/');
                setcookie('password', $password, $expire, '/');
                $this->_user = $_SESSION['user'];
                return;
            }
        }
        throw new RuntimeException('Wrong user name or password!');
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

<?php
final class User
{
    use Getter;

    const GUEST = 'Guest';

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
            session_destroy();
            session_write_close();
            unset($_COOKIE['id']);
            unset($_COOKIE['password']);
            setcookie(session_name(), '', 0, '/');
            setcookie('id', '', 0, '/');
            setcookie('password', '', 0, '/');
        }
    }

    private function check($id, $password)
    {
        $db = Sys::db();
        $user = $db->getOne('select * from tbl_user where id = ?', [$id]);
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

    public function name()
    {
        return $this->_user ? $this->_user['name'] : self::GUEST;
    }

    public function isGuest()
    {
        return $this->_user == null;
    }
}

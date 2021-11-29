<?php
final class Db extends PDO
{
    public function __construct()
    {
        parent::__construct(PDO_DSN, DB_USER, DB_PASSWORD, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION SQL_BIG_SELECTS=1'
        ));
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function getOne($sql, $paras = [])
    {
        $st = $this->prepare($sql);
        $st->execute($paras);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($sql, $paras = [])
    {
        $st = $this->prepare($sql);
        $st->execute($paras);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}

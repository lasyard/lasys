<?php
final class Db extends PDO
{
    public function __construct()
    {
        parent::__construct(PDO_DSN, DB_USER, DB_PASSWORD, [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION SQL_BIG_SELECTS=1; SET TIME_ZONE = "+08:00";',
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private static function bindParas($st, $paras)
    {
        for ($i = 1; $i <= sizeof($paras); ++$i) {
            $p = $paras[$i - 1];
            if (is_int($p)) {
                $st->bindValue($i, $p, PDO::PARAM_INT);
            } else if (is_bool($p)) {
                $st->bindValue($i, $p, PDO::PARAM_BOOL);
            } else {
                $st->bindValue($i, $p, PDO::PARAM_STR);
            }
        }
    }

    private function runSql($sql, $paras)
    {
        $st = $this->prepare($sql);
        self::bindParas($st, $paras);
        $st->execute();
        return $st;
    }

    public function run($sql, ...$paras)
    {

        $st = $this->runSql($sql, $paras);
        return $st->rowCount();
    }

    public function getOne($sql, ...$paras)
    {
        $st = $this->runSql($sql, $paras);
        return $st->fetch();
    }

    public function count($tbl)
    {
        $r = $this->getOne('select count(1) as ct from ' . $tbl);
        return $r['ct'];
    }

    public function getAll($sql, ...$paras)
    {
        $st = $this->runSql($sql, $paras);
        return $st->fetchAll();
    }

    public function getDataSet($sql, ...$paras)
    {
        $st = $this->runSql($sql, $paras);
        $columns = [];
        for ($i = 0; $i < $st->columnCount(); ++$i) {
            $columns[$st->getColumnMeta($i)['name']] = $i;
        }
        $data = $st->fetchAll(PDO::FETCH_NUM);
        return compact('columns', 'data');
    }

    public function insert($tbl, $kv, $trans = null)
    {
        $row = $this->insertBatch($tbl, array_keys($kv), [array_values($kv)], $trans);
        $id = null;
        if ($row == 1) {
            $id = $this->lastInsertId();
        }
        return [$row, $id];
    }

    public function insertBatch($tbl, $k, $vs, $trans = null)
    {
        $trans ??= function ($k) {
            return '?';
        };
        $sql = 'insert into `' . $tbl . '`(' . join(', ', array_map(function ($k) {
            return '`' . $k . '`';
        }, $k)) . ') values(' . join(', ', array_map($trans, $k)) . ')';
        $st = $this->prepare($sql);
        $row = 0;
        foreach ($vs as $v) {
            self::bindParas($st, $v);
            $st->execute();
            $row += $st->rowCount();
        }
        return $row;
    }

    public function update($tbl, $kvPrimary, $kv, $trans = null)
    {
        $trans ??= function ($k) {
            return '?';
        };
        $sql = 'update ' . $tbl . ' set ' . join(', ', array_map(function ($k) use ($trans) {
            return '`' . $k . '` = ' . $trans($k);
        }, array_keys($kv))) . ' where ' . join(' and ', array_map(function ($k) {
            return '`' . $k . '` = ?';
        }, array_keys($kvPrimary)));
        $st = $this->runSql($sql, array_merge(array_values($kv), array_values($kvPrimary)));
        return $st->rowCount();
    }

    public function delete($tbl, $kv)
    {
        $sql = 'delete from `' . $tbl . '` where '
            . join(' and ', array_map(function ($k) {
                return '`' . $k . '` = ?';
            }, array_keys($kv)));
        $st = $this->runSql($sql, array_values($kv));
        return $st->rowCount();
    }

    public function getColumns($tbl)
    {
        // Add `full` to get `Comment`.
        return $this->getAll('show full columns from ' . $tbl);
    }

    public function getLastModTime($tbl)
    {
        $r = $this->getOne(
            <<<'EOS'
            select
                unix_timestamp(create_time) as ctime,
                unix_timestamp(update_time) as mtime
            from information_schema.tables
            where
                table_name = ?
            EOS,
            $tbl
        );
        if (isset($r['mtime'])) {
            return $r['mtime'];
        }
        return $r['ctime'];
    }

    // Does not support dependencies induced by foreign keys.
    public function dump($path, $tables = '*', $excludes = null, $whole = false)
    {
        if ($tables == '*') {
            $tables = [];
            $result = $this->query('show tables');
            while ($table = $result->fetchColumn(0)) {
                if ($excludes && in_array($table, $excludes)) {
                    continue;
                }
                $tables[] = $table;
            }
        } else {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }
        $colExcludes = array_fill_keys($tables, null);
        if ($excludes) {
            foreach ($excludes as $exclude) {
                list($table, $col) = explode('.', $exclude, 2);
                if (array_key_exists($table, $colExcludes) && $col) {
                    if ($colExcludes[$table] === null) {
                        $colExcludes[$table] = [];
                    }
                    $colExcludes[$table][] = $col;
                }
            }
        }
        if ($whole) {
            $fh = File::openForWriting($path . '.sql');
            $this->dumpSettings($fh);
            fwrite($fh, PHP_EOL);
            foreach ($tables as $table) {
                $this->dumpTable($fh, $table, $colExcludes[$table]);
            }
            fclose($fh);
        } else {
            if (is_dir($path)) {
                $dir = opendir($path);
                while (($file = readdir($dir)) !== false) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    unlink($path . '/' . $file);
                }
                closedir($dir);
            }
            foreach ($tables as $table) {
                $fh = File::openForWriting($path . '/' . $table . '.sql');
                $this->dumpSettings($fh);
                fwrite($fh, PHP_EOL);
                $this->dumpTable($fh, $table, $colExcludes[$table]);
                fclose($fh);
            }
        }
    }

    private function dumpSettings($fh)
    {
        fwrite($fh, "SET NAMES 'utf8mb4';" . PHP_EOL);
        fwrite($fh, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";" . PHP_EOL);
        fwrite($fh, "SET time_zone = \"+08:00\";" . PHP_EOL);
    }

    private function dumpTable($fh, $table, $excludes = null)
    {
        fwrite($fh, "DROP TABLE IF EXISTS `" . $table . "`;" . PHP_EOL);
        $result = $this->query('show create table ' . $table);
        $sql = $result->fetchColumn(1);
        // Remove the collate setting to be compatible with old mysql versions.
        $sql = preg_replace('/\s+COLLATE=\w+/i', '', $sql);
        fwrite($fh, $sql . ";" . PHP_EOL . PHP_EOL);
        $result = $this->query('select * from ' . $table);
        $fields = [];
        $fieldStrs = [];
        for ($i = 0; $i < $result->columnCount(); ++$i) {
            $meta = $result->getColumnMeta($i);
            $name = $meta['name'];
            if ($excludes && in_array($name, $excludes)) {
                // skipped
                $fields[$name] = false;
                continue;
            }
            $fieldStrs[] = "`$name`";
            switch ($meta['native_type']) {
                case 'LONG':
                case 'TINY':
                case 'SHORT':
                case 'FLOAT':
                case 'DOUBLE':
                case 'INT24':
                case 'LONGLONG':
                    $fields[$name] = 'number';
                    break;
                case 'GEOMETRY':
                    $fields[$name] = 'hex';
                    break;
                default:
                    $fields[$name] = 'string';
            }
        }
        $insertLine = "INSERT INTO `" . $table . "` (" . join(', ', $fieldStrs) . ") VALUES" . PHP_EOL;
        $row = $result->fetch(PDO::FETCH_ASSOC);
        while ($row) {
            fwrite($fh, $insertLine);
            for ($count = 0;;) {
                $v = [];
                foreach ($row as $key => $value) {
                    if (!$fields[$key]) {
                        continue;
                    }
                    if (!isset($value)) {
                        $v[] = 'NULL';
                    } elseif ($fields[$key] === 'string') {
                        $v[] = $this->quote($value);
                    } elseif ($fields[$key] === 'hex') {
                        $v[] = '0x' . bin2hex($value);
                    } else {
                        $v[] = $value;
                    }
                }
                fwrite($fh, "(" . join(', ', $v) . ")");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $count++;
                if ($row && $count < 100) {
                    fwrite($fh, ',' . PHP_EOL);
                } else {
                    fwrite($fh, ';' . PHP_EOL);
                    break;
                }
            }
        }
        fwrite($fh, PHP_EOL);
    }
}

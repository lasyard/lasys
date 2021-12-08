<?php
final class Db extends PDO
{
    public function __construct()
    {
        parent::__construct(PDO_DSN, DB_USER, DB_PASSWORD, [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION SQL_BIG_SELECTS=1',
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }

    public function getOne($sql, $paras = [])
    {
        $st = $this->prepare($sql);
        $st->execute($paras);
        return $st->fetch();
    }

    public function getAll($sql, $paras = [])
    {
        $st = $this->prepare($sql);
        $st->execute($paras);
        return $st->fetchAll();
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
            $file = $path . '.sql';
            $fh = fopen($file, 'w');
            if (!$fh) {
                mkdir(dirname($file), 0775, true);
                $fh = fopen($file, 'w');
            }
            if (!$fh) {
                throw new RuntimeException('Open file "' . $file . '" failed!');
            }
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
            } else {
                mkdir($path, 0775, true);
            }
            foreach ($tables as $table) {
                $file = $path . '/' . $table . '.sql';
                $fh = fopen($file, 'w');
                if (!$fh) {
                    throw new RuntimeException('Open file "' . $file . '" failed!');
                }
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
        fwrite($fh, $sql . ";" . PHP_EOL . PHP_EOL);
        $result = $this->query('select * from ' . $table);
        $fields = [];
        $fieldStrs = [];
        for ($i = 0; $i < $result->columnCount(); ++$i) {
            $meta = $result->getColumnMeta($i);
            $name = $meta['name'];
            $fields[$name] = [];
            if ($excludes && in_array($name, $excludes)) {
                $fields[$name]['skipped'] = true;
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
                case 'TIMESTAMP':
                    $quote = false;
                    break;
                default:
                    $quote = true;
            }
            $fields[$name]['quote'] = $quote;
        }
        $insertLine = "INSERT INTO `" . $table . "` (" . join(', ', $fieldStrs) . ") VALUES" . PHP_EOL;
        $row = $result->fetch();
        while ($row) {
            fwrite($fh, $insertLine);
            for ($count = 0;;) {
                $v = [];
                foreach ($row as $key => $value) {
                    if ($fields[$key]['skipped']) {
                        continue;
                    }
                    if (!isset($value)) {
                        $v[] = 'NULL';
                    } elseif ($fields[$key]['quote']) {
                        $v[] = $this->quote($value);
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

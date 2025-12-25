<?php
final class Meta
{
    public const META_FILE = 'list.json';

    private function __construct() {}

    public static function loadFileList($path, $exclude = null)
    {
        $files = [];
        $dh = @opendir($path);
        if (!$dh) {
            return $files;
        }
        while (($file = readdir($dh)) !== false) {
            if ($file == Meta::META_FILE) {
                $files = array_merge_recursive($files, Meta::load($path));
                continue;
            }
            if (fnmatch('.*', $file) || ($exclude && $exclude($file))) {
                continue;
            }
            $files[$file]['isDir'] = is_dir($path . DS . $file);
        }
        closedir($dh);
        // Filter out non-existing files.
        $files1 = array_filter($files, function ($v) {
            return array_key_exists('isDir', $v);
        });
        // Remove info of missing files from meta file.
        if (count($files1) < count($files)) {
            self::save($path, $files1);
        }
        return $files1;
    }

    public static function load($path)
    {
        return json_decode(file_get_contents($path . DS . self::META_FILE), true);
    }

    public static function save($path, $files)
    {
        $json = [];
        foreach ($files as $name => $file) {
            $res = Arr::transKeys($file, 'title', 'desc', 'time', 'uid', 'uname');
            if (!empty($res)) {
                $json[$name] = $res;
            }
        }
        File::mkdir($path);
        file_put_contents(
            $path . DS . self::META_FILE,
            json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }
}

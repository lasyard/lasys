<?php
final class DoUpload extends Traits
{
    private static $instance = null;

    public static function get()
    {
        self::$instance = self::$instance ?? new DoUpload();
        return self::$instance;
    }

    public function forSelf($conf, $oldConf)
    {
        Arr::copyNonExistingKeys(
            $conf,
            $oldConf,
            FileActions::UPLOAD_TITLE,
            FileActions::ACCEPT,
            FileActions::SIZE_LIMIT,
            FileActions::ORDER,
        );
        if (isset($conf[Config::READ_ONLY]) && $conf[Config::READ_ONLY]) {
            return $conf;
        }
        $conf[Config::LIST][$conf[Config::DEFAULT_ITEM]][Server::POST] = FileActions::post()->priv(User::EDIT);
        $conf[Config::LIST][FileActions::UPLOAD_ITEM] = [
            Config::TITLE => $conf[FileActions::UPLOAD_TITLE] ?? FileActions::DEFAULT[FileActions::UPLOAD_TITLE],
            Config::BUTTON => Icon::UPLOAD,
            Server::GET => FileActions::uploadForm()->priv(User::EDIT),
        ];
        return $conf;
    }

    public function forChild($conf, $oldConf)
    {
        $conf[Config::TRAITS][] = self::$instance;
        return $conf;
    }
}

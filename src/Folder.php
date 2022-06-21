<?php
namespace Webilia\WP;

/**
 * Class Folder
 * @package Utils
 */
class Folder
{
    /**
     * @param string $path
     * @param string $filter
     * @return array<string>
     */
    public static function files(string $path, string $filter = '.'): array
    {
        // Path doesn't exists
        if(!self::exists($path)) return [];

        $files = [];
        if($handle = opendir($path))
        {
            while(($entry = readdir($handle)) !== false)
            {
                if($entry == '.' or $entry == '..' or is_dir($entry)) continue;
                if(!preg_match("/$filter/", $entry)) continue;

                $files[] = $entry;
            }

            closedir($handle);
        }

        return $files;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function create(string $path): bool
    {
        // Directory Exists Already
        if(Folder::exists($path)) return true;

        // Check Parent Directory
        $parent = substr($path, 0, (strrpos($path, '/', -2) + 1));
        $return = Folder::create($parent);

        // Create Directory
        return ($return && is_writable($parent)) ? mkdir($path, 0755) : false;
    }
}

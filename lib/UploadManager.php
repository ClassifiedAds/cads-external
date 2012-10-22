<?php

define('LIB_PATH',  realpath(dirname(__FILE__)));
define('BASE_PATH', realpath(LIB_PATH . DIRECTORY_SEPARATOR . '..'));
define('TMP_DIR',   realpath(BASE_PATH . DIRECTORY_SEPARATOR . 'tmp'));


include_once BASE_PATH . DIRECTORY_SEPARATOR . '__common.inc';

class UploadManager
{
    /**
     * Takes an uploaded file, move it to a persistent storage,
     * and records the file metadata.
     *
     * @param file An array of file metadata as used in $_FILES.
     */
    public static function create(array $file, $owner = null) {
        if($file && $file['error'] == UPLOAD_ERR_OK) {

            $id = self::uuid();
            $name = esc($file['name']);
            $type = esc($file['type']);
            $size = esc($file['size']);

            $fullpath = TMP_DIR . DIRECTORY_SEPARATOR . $id;
            $relativepath = self::relativePath(BASE_PATH, $fullpath);

            if(move_uploaded_file($file['tmp_name'], $fullpath)) {

                $sql = "insert into file_storage (
                            `id`
                        ,   `name`
                        ,   `type`
                        ,   `size`
                        ,   `fullpath`
                        ,   `relativepath`
                        ,   `created`
                        ,   `owner`
                        ) values (
                            '$id'
                        ,   '$name'
                        ,   '$type'
                        ,   '$size'
                        ,   '$fullpath'
                        ,   '$relativepath'
                        ,   NOW()
                        ,   '$owner'
                    )";

                if(super_query($sql)) {
                    return array('id' => $id, 'fullpath' => $fullpath, 'relativepath' => $relativepath, 'code' => 0);
                }
            }
        }
        return false;
    }

    /**
     * Removes all uploaded files before a specific date.
     *
     * @param date All files uploaded before this date will be removed.
     */
    public static function remove($date) {

    }

    public static function get($fileid) {
        $id = esc($fileid);
        $result = slower_query("select `id`, `fullpath`, `relativepath`, `name` from `file_storage` where `id` = '$id'");

        if($result) {
            return mysql_fetch_assoc($result);
        }

        return false;
    }

    public static function uuid() {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    public static function relativePath($from, $to, $ps = DIRECTORY_SEPARATOR)
    {
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));
        while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0]))
        {
            array_shift($arFrom);
            array_shift($arTo);
        }
        return str_pad("", count($arFrom) * 3, '..' . $ps).implode($ps, $arTo);
    }
}

?>

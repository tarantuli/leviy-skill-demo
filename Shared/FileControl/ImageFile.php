<?php
namespace Shared\FileControl;

/**
 * (summary missing)
 */
class ImageFile extends File
{
    /**********************
     *   Static methods   *
     *********************/

    public static function getMimetypes()
    {
        return [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/vnd.wap.wbmp',
        ];
    }
}

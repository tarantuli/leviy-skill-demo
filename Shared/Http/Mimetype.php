<?php
namespace Shared\Http;

use Shared\FileControl\Exceptions\FileNotFoundException;
use Shared\FileControl\TemporaryFile;

/**
 * Contains methods regarding mimetypes
 */
class Mimetype
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns the mimetype of the given content string
     *
     * @param  string  $content
     *
     * @return  string
     */
    public static function fromContent(string $content)
    {
        $tempFile = TemporaryFile::write($content);

        return self::fromFile($tempFile);
    }

    /**
     * Returns the mimetype of a file given by its location
     *
     * @param  string  $filename  The file location
     *
     * @return  string
     *
     * @throws  FileNotFoundException
     */
    public static function fromFile(string $filename)
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException($filename);
        }

        $mimescanner = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype    = finfo_file($mimescanner, $filename);

        finfo_close($mimescanner);

        return $mimetype;
    }
}

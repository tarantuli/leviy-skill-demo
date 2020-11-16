<?php
namespace Shared\FileControl;

/**
 * (summary missing)
 */
class TemporaryFile
{
    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  string[]
     */
    private static $temporaryFiles = [];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Creates a temporary file
     *
     * @param  null  $content
     *
     * @return  string
     *
     * @throws  Exceptions\CantCreateTemporaryFileException
     */
    public static function create($content = null)
    {
        $tempFilename = @ tempnam(sys_get_temp_dir(), 'file');

        if ($tempFilename === false) {
            throw new Exceptions\CantCreateTemporaryFileException();
        }

        self::$temporaryFiles[] = $tempFilename;

        if ($content !== null) {
            // Use fopen/fwrite/fclose instead of file_put_contents to bypass memory problems
            $fh = fopen($tempFilename, 'w');

            fwrite($fh, $content);
            fclose($fh);
        }

        return $tempFilename;
    }

    /**
     * Deletes remaining temporary files created by write()
     *
     * @return  void
     */
    public static function delete()
    {
        foreach (self::$temporaryFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Writes the content to a temporary file and returns the location of that file
     *
     * @param  string  $content
     *
     * @return  string
     */
    public static function write(string $content)
    {
        $tempFilename = self::create();

        // Use fopen/fwrite/fclose instead of file_put_contents to bypass memory problems
        $fh = fopen($tempFilename, 'w');

        fwrite($fh, $content);
        fclose($fh);

        return $tempFilename;
    }
}

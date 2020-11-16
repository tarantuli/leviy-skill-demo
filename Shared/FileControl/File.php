<?php
namespace Shared\FileControl;

use Shared\Http\Mimetype;

/**
 * (summary missing)
 */
class File
{
    /*****************
     *   Constants   *
     ****************/

    public const CONTENT_IS_PATH = true;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string  $path
     * @param  string  $content
     *
     * @return  bool
     */
    public static function setContents(string $path, string $content)
    {
        return self::ensureExistence($path, $content);
    }

    /**
     * @param  string  $path
     * @param  string  $defaultContent
     *
     * @return  bool|string
     */
    public static function getContents(string $path, string $defaultContent = '')
    {
        if (!self::exists($path)) {
            self::ensureExistence($path, $defaultContent);

            return $defaultContent;
        }
        else {
            return file_get_contents($path);
        }
    }

    /**
     * Function to ensure the existance of a file. If it does not exist, it tries to
     * create the directory and then the file
     *
     * @param  string       $path
     * @param  string|null  $content
     * @param  string|null  $defaultContent
     * @param  bool         $contentIsPath
     *
     * @return  bool
     */
    public static function ensureExistence(string $path, string $content = null, ?string $defaultContent = null, bool $contentIsPath = false)
    {
        $path      = self::normalizePath($path);
        $directory = pathinfo($path, PATHINFO_DIRNAME);

        Directory::ensureExistence($directory);

        // If it doesn't exist yet, touch it
        if (!file_exists($path)) {
            @ touch($path);

            if ($defaultContent !== null) {
                if ($contentIsPath) {
                    $defaultContent = file_get_contents($defaultContent);
                }

                file_put_contents($path, $defaultContent);
            }
        }

        // Ensure it exists now and that it's a file
        $itExists = file_exists($path) && is_file($path);

        if ($itExists && $content !== null) {
            if ($content instanceof File) {
                $content = $content->getContent();
            }

            if ($contentIsPath) {
                $content = file_get_contents($content);
            }

            file_put_contents($path, $content);
        }

        return $itExists;
    }

    /**
     * Function to ensure the existance of a file. If it does not exist, it tries to
     * create the directory and then the file
     *
     * @param  string       $path
     * @param  string|null  $file
     *
     * @return  bool
     */
    public static function exists(string $path, string $file = null)
    {
        if ($file !== null) {
            $path .= (substr($path, -1) === DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR;
            $path .= $file;
        }

        return file_exists($path) && is_file($path);
    }

    public static function fromContent($value)
    {
        return new self($value);
    }

    /**
     * @param  string  $path
     *
     * @return  string
     */
    public static function normalizeAndEnsure(string $path)
    {
        $path = self::normalizePath($path);

        self::ensureExistence($path);

        return $path;
    }

    /**
     * Normalizes a file path, replacing .'s and ..'s and removes empty elements
     *
     * @param  string  $path
     *
     * @return  string
     */
    public static function normalizePath(string $path)
    {
        if (substr($path, 0, 1) === '.') {
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }

        // An absolute path should either start with a slash (unix), or have a colon on the second position (Windows)
        if (substr($path, 0, 1) !== SLASH && substr($path, 1, 1) !== ':') {
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }

        $path      = str_replace(BACKSLASH, SLASH, $path);
        $startsWithSlash = substr($path, 0, 1) === SLASH;
        $parts     = array_filter(explode(SLASH, $path), 'strlen');
        $absolutes = [];

        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }

            if ('..' == $part) {
                array_pop($absolutes);
            }
            else {
                $absolutes[] = $part;
            }
        }

        $path = implode(DIRECTORY_SEPARATOR, $absolutes);

        if ($startsWithSlash) {
            $path = SLASH . $path;
        }

        return $path;
    }

    /**
     * Replaces illegal and unsafe characters by safe characters in the given file
     * name
     *
     * @param  string  $filename
     *
     * @return  string
     */
    public static function createSafeFilename(string $filename)
    {
        return strtolower(strtr($filename, '\\/:*?"<>|', '__.#.\'[]_'));
    }


    /**************************
     *   Instance variables   *
     *************************/

    private string $content;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  string  $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function __toString()
    {
        return $this->content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getHash()
    {
        return sha1($this->content);
    }

    public function isImage()
    {
        return in_array($this->getMimetype(), ImageFile::getMimetypes());
    }

    public function getMimetype()
    {
        return Mimetype::fromContent($this->content);
    }
}

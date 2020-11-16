<?php
namespace Shared\FileControl;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Shared\DataControl\Str;

/**
 * (summary missing)
 */
class Directory
{
    /*****************
     *   Constants   *
     ****************/

    /**
     * @var  string[]
     */
    private const SPECIAL_FOLDERS = ['.', '..'];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns whether the given directory is empty or only contains other empty
     * directories
     *
     * @param  string  $path
     *
     * @return  bool
     */
    public static function containsOnlyEmptyDirectories(string $path)
    {
        if (!is_dir($path)) {
            return false;
        }

        $directoryContent = scandir($path);

        if (count($directoryContent) <= 2) {
            return true;
        }

        foreach ($directoryContent as $subPath) {
            if (in_array($subPath, self::SPECIAL_FOLDERS)) {
                continue;
            }

            if (!self::containsOnlyEmptyDirectories($path . DIRECTORY_SEPARATOR . $subPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes all directories in the given path that are empty or only contain other
     * empty directories
     *
     * @param  string  $path
     *
     * @return  void
     *
     * @throws  Exceptions\DirectoryNotFoundException
     */
    public static function deleteEmptyDirectoriesRecursively(string $path)
    {
        if (!is_dir($path)) {
            throw new Exceptions\DirectoryNotFoundException($path);
        }

        if (self::containsOnlyEmptyDirectories($path)) {
            self::deleteRecursively($path);
        }
        else {
            $directoryContent = scandir($path);

            foreach ($directoryContent as $subPath) {
                if (in_array($subPath, self::SPECIAL_FOLDERS)) {
                    continue;
                }

                $newPath = $path . DIRECTORY_SEPARATOR . $subPath;

                if (is_dir($newPath)) {
                    self::deleteEmptyDirectoriesRecursively($newPath);
                }
            }
        }
    }

    /**
     * Function to ensure the existance of a directory. If it does not exist, it
     * tries to create it
     *
     * @param  string  $directory
     *
     * @return  bool
     */
    public static function ensureExistence(string $directory)
    {
        $directory = File::normalizePath($directory);

        // If it doesn't exist yet, create it
        if (!file_exists($directory)) {
            self::createRecursively($directory);
        }

        // Ensure that it exists now and that it's a directory
        return file_exists($directory) && is_dir($directory);
    }

    /**
     * @param  string  &$directory
     *
     * @return  bool
     */
    public static function exists(& $directory)
    {
        $directory = File::normalizePath($directory);

        // Check if it exists now and if it's a directory
        return file_exists($directory) && is_dir($directory);
    }

    /**
     * Recursively scans for files in the given directory
     *
     * @param  string    $directory               The root directory
     * @param  string    $pattern                 The regex pattern that filenames must match to be returned
     * @param  string    $modifiers               Regex modifiers for the pattern
     * @param  int|null  $directoryIteratorFlags
     *
     * @return  RegexIterator
     */
    public static function getIterator(string $directory, string $pattern = '.+', string $modifiers = '', int $directoryIteratorFlags = null)
    {
        if ($directoryIteratorFlags === null) {
            $directoryIteratorFlags
                = RecursiveDirectoryIterator::KEY_AS_FILENAME | RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | RecursiveDirectoryIterator::SKIP_DOTS;
        }

        $directoryIterator = new RecursiveDirectoryIterator($directory, $directoryIteratorFlags);

        return new RegexIterator(
            new RecursiveIteratorIterator($directoryIterator),
            sprintf('/%s/%s', str_replace('/', '\\/', $pattern), $modifiers),
            RegexIterator::MATCH,
            RegexIterator::USE_KEY
        );
    }

    /**
     * @param  string  $path
     *
     * @return  string
     */
    public static function normalize(string $path)
    {
        return File::normalizePath($path);
    }

    /**
     * @param  string  $path
     *
     * @return  string
     */
    public static function normalizeAndEnsure(string $path)
    {
        $path = File::normalizePath($path);

        self::ensureExistence($path);

        return $path;
    }

    /**
     * Creates the given directory, starting from the root to the deepest level
     *
     * @param  string  $directory
     *
     * @return  bool
     *
     * @throws  Exceptions\DirectoryCreationFailedException
     */
    public static function createRecursively(string $directory)
    {
        // Start in the working directory if possible
        if (Str::startsWith($directory, getcwd())) {
            $currentDirectory = getcwd() . DIRECTORY_SEPARATOR;
            $directory = substr($directory, strlen($currentDirectory));
        }
        else {
            $currentDirectory = '';
        }

        // Check each remaining part in order
        $parts = explode(SLASH, str_replace(BACKSLASH, SLASH, $directory));

        foreach ($parts as $part) {
            $currentDirectory .= $part . DIRECTORY_SEPARATOR;

            if (file_exists($currentDirectory) && is_dir($currentDirectory)) {
                continue;
            }

            if (@ mkdir($currentDirectory) === false) {
                throw new Exceptions\DirectoryCreationFailedException($currentDirectory);
            }
        }

        return true;
    }

    /**
     * Deletes the given path and everything in it
     *
     * @param  string  $path
     *
     * @return  void
     */
    public static function deleteRecursively(string $path)
    {
        $dir = opendir($path);

        while (false !== $subPath = readdir($dir)) {
            if (in_array($subPath, self::SPECIAL_FOLDERS)) {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $subPath;

            if (is_dir($fullPath)) {
                self::deleteRecursively($fullPath);
            }
            else {
                unlink($fullPath);
            }
        }

        closedir($dir);
        rmdir($path);
    }

    public static function getSize(string $directory): int
    {
        $size = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}

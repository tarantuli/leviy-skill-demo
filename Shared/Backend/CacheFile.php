<?php
namespace Shared\Backend;

use Shared\DataControl\DataObjects\JsonFile;
use Shared\FileControl\File;
use Shared\Providers\AbstractSingletonProvider;

class CacheFile extends AbstractSingletonProvider
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  string
     */
    private $directory;

    /**
     * @var  JsonFile[]
     */
    private $files = [];


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  string  $directory
     */
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory . DIRECTORY_SEPARATOR;
    }

    /**
     * @param  string  $class
     * @param  string  $extension
     *
     * @return  JsonFile
     */
    public function forClass(string $class, string $extension = 'json'): ?JsonFile
    {
        if (!$this->directory) {
            return null;
        }

        $filename = sprintf('%s%s.%s', $this->directory, str_replace(BACKSLASH, '.', $class), $extension);

        File::ensureExistence($filename, null, '[]');

        return JsonFile::forFilepath($filename);
    }

    public function saveAll()
    {
        foreach ($this->files as $file) {
            $file->save();
        }
    }
}

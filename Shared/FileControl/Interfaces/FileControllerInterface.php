<?php
namespace Shared\FileControl\Interfaces;

/**
 * (summary missing)
 */
interface FileControllerInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  string  $path
     *
     * @return  string
     */
    public function read(string $path): string;

    /**
     * @param  string  $path
     * @param  string  $content
     *
     * @return  bool
     */
    public function write(string $path, string $content): bool;
}

<?php
namespace Shared\FileControl\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class DirectoryNotFoundException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * DirectoryNotFoundException constructor
     *
     * @param  mixed  $directory
     */
    public function __construct($directory)
    {
        parent::__construct($directory);
    }

    public function getPattern(): string
    {
        return 'Directory %s not found';
    }
}

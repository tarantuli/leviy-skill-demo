<?php
namespace Shared\FileControl\Exceptions;

use Shared\Exceptions\AbstractBaseException;

class DirectoryCreationFailedException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * DirectoryCreationFailedException constructor
     *
     * @param  mixed  $directory
     */
    public function __construct($directory)
    {
        parent::__construct($directory, getcwd());
    }

    public function getPattern(): string
    {
        return 'Failed to create directory %s with working directory %s';
    }
}

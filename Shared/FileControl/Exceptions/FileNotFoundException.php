<?php
namespace Shared\FileControl\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class FileNotFoundException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * FileNotFoundException constructor
     *
     * @param  mixed  $file
     */
    public function __construct($file)
    {
        parent::__construct($file, getcwd());
    }

    public function getPattern(): string
    {
        return 'File %s not found in working directory %s';
    }
}

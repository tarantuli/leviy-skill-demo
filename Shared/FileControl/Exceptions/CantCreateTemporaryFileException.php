<?php
namespace Shared\FileControl\Exceptions;

use Shared\Exceptions\AbstractBaseException;

class CantCreateTemporaryFileException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * CantCreateTemporaryFileException constructor
     *
     * @hasNoParams
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the message pattern
     *
     * @return  string
     */
    public function getPattern(): string
    {
        return 'failed to create a temporary file';
    }
}

<?php
namespace Shared\System\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class AlmostOutOfMemoryException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * AlmostOutOfMemoryException constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getPattern(): string
    {
        return 'Almost of out memory';
    }
}

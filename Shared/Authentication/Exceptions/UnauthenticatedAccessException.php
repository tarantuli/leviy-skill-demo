<?php
namespace Shared\Authentication\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class UnauthenticatedAccessException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * UnauthenticatedAccessException constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getPattern(): string
    {
        return 'No valid authentication found';
    }
}

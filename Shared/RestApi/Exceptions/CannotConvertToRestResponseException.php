<?php
namespace Shared\RestApi\Exceptions;

use Shared\Exceptions\AbstractBaseException;

class CannotConvertToRestResponseException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * CannotConvertToRestResponseException constructor
     *
     * @param  string  $class
     */
    public function __construct(string $class)
    {
        parent::__construct($class);
    }

    public function getPattern(): string
    {
        return 'Cannot convert an object of class %s to a REST response';
    }
}

<?php
namespace Shared\Exceptions;

/**
 * (summary missing)
 */
class InvalidInputException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * InvalidInputException constructor
     *
     * @param  mixed  $value
     * @param  mixed  $type
     */
    public function __construct($value, $type)
    {
        parent::__construct($value, $type);
    }

    /**
     * @return  string
     */
    public function getPattern(): string
    {
        return '%s is not a valid %l';
    }
}

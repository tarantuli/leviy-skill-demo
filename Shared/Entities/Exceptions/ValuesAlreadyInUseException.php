<?php
namespace Shared\Entities\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class ValuesAlreadyInUseException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * ValuesAlreadyInUseException constructor
     *
     * @param  array   $values
     * @param  string  $class
     */
    public function __construct(array $values, string $class)
    {
        parent::__construct($values, $class);
    }

    public function getPattern(): string
    {
        return 'Values %s are already in use in class %s';
    }
}

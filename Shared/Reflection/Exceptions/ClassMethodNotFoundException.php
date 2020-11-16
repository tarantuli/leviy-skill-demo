<?php
namespace Shared\Reflection\Exceptions;

use Shared\Exceptions\AbstractBaseException;

class ClassMethodNotFoundException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * ClassMethodNotFoundException constructor
     *
     * @param  mixed  $method
     * @param  mixed  $class
     */
    public function __construct($method, $class)
    {
        parent::__construct($method, $class);
    }

    public function getPattern(): string
    {
        return 'Method %s does not exist in class %s';
    }
}

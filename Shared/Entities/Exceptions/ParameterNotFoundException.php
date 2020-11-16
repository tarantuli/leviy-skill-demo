<?php
namespace Shared\Entities\Exceptions;

use Shared\Exceptions\AbstractBaseException;

class ParameterNotFoundException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * ParameterNotFoundException constructor
     *
     * @param  string  $method
     * @param  string  $parameter
     */
    public function __construct(string $method, string $parameter)
    {
        parent::__construct($method, $parameter);
    }

    public function getPattern(): string
    {
        return 'method %s has no parameter named %s';
    }
}

<?php
namespace Shared\Reflection\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class InterfaceNotFoundException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * InterfaceNotFoundException constructor
     *
     * @param  string  $interface
     */
    public function __construct(string $interface)
    {
        parent::__construct($interface);
    }

    public function getPattern(): string
    {
        return 'Interface %s not found';
    }
}

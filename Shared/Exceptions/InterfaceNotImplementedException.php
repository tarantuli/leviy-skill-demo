<?php
namespace Shared\Exceptions;

/**
 * (summary missing)
 */
class InterfaceNotImplementedException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * InterfaceNotImplementedException constructor
     *
     * @param  string  $class
     * @param  string  $interface
     */
    public function __construct(string $class, string $interface)
    {
        parent::__construct($class, $interface);
    }

    public function getPattern(): string
    {
        return '%s does not implement interface %s';
    }
}

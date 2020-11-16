<?php
namespace Shared\Exceptions;

/**
 * (summary missing)
 */
class ClassNotInitializedException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * ClassNotInitializedException constructor
     *
     * @param  string  $class
     */
    public function __construct(string $class)
    {
        parent::__construct($class);
    }

    public function getPattern(): string
    {
        return 'Class %s was not initialized';
    }
}

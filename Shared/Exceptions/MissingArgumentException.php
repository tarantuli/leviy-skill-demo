<?php
namespace Shared\Exceptions;

/**
 * (summary missing)
 */
class MissingArgumentException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * MissingArgumentException constructor
     *
     * @param  mixed  $argument
     * @param  array  $values
     */
    public function __construct($argument, array $values)
    {
        parent::__construct($argument, $values);
    }

    public function getPattern(): string
    {
        return 'Missing argument %s in %s';
    }
}

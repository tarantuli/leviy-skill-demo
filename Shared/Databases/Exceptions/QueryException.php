<?php
namespace Shared\Databases\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class QueryException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * QueryException constructor
     *
     * @param  int     $code
     * @param  string  $message
     * @param  string  $query
     * @param  array   $arguments
     */
    public function __construct(int $code, string $message, string $query, array $arguments)
    {
        parent::__construct($code, $message, $query, $arguments);
    }

    public function getPattern(): string
    {
        return 'Error %u: %s when executing %s with arguments %s';
    }
}

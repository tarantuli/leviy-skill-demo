<?php
namespace Shared\Databases\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class DatabaseSelectionException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * DatabaseSelectionException constructor
     *
     * @param  string  $database
     */
    public function __construct(string $database)
    {
        parent::__construct($database);
    }

    public function getPattern(): string
    {
        return 'Cannot select database %s';
    }
}

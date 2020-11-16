<?php
namespace Shared\Databases\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class TableSelectionException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * TableSelectionException constructor
     *
     * @param  string  $table
     */
    public function __construct(string $table)
    {
        parent::__construct($table);
    }

    public function getPattern(): string
    {
        return 'Cannot select table %s';
    }
}

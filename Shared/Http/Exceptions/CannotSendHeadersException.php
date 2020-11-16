<?php
namespace Shared\Http\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class CannotSendHeadersException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * CannotSendHeadersException constructor
     *
     * @param  string  $file
     * @param  int     $lineNumber
     */
    public function __construct(string $file, int $lineNumber)
    {
        parent::__construct($file, $lineNumber);
    }

    public function getPattern(): string
    {
        return 'Cannot send headers, output started in %s:%u';
    }
}

<?php
namespace Shared\Exceptions;

/**
 * (summary missing)
 */
class ErrorException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * ErrorException constructor
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int     $lineNumber
     */
    public function __construct(string $message, string $file, int $lineNumber)
    {
        parent::__construct($message, $file, $lineNumber);
    }

    public function getPattern(): string
    {
        return 'Uncaught error: %s in %s:%u';
    }
}

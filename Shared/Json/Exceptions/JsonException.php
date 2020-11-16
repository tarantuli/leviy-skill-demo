<?php
namespace Shared\Json\Exceptions;

use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class JsonException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * JsonException constructor
     *
     * @param  string  $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getPattern(): string
    {
        return 'JSON error: %s';
    }
}

<?php
namespace Shared\Tokens\Exceptions;

use Shared\Exceptions\AbstractBaseException;

class InvalidTokenException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * InvalidTokenException constructor
     *
     * @param  string  $token
     */
    public function __construct(string $token)
    {
        parent::__construct($token);
    }

    public function getPattern(): string
    {
        return 'Invalid token %s';
    }
}

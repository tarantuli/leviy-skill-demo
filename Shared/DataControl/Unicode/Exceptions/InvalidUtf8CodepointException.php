<?php
namespace Shared\DataControl\Unicode\Exceptions;

use Shared\Exceptions\AbstractBaseException;

class InvalidUtf8CodepointException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * InvalidUtf8CodepointException constructor
     *
     * @param  int  $codepoint
     */
    public function __construct(int $codepoint)
    {
        parent::__construct($codepoint);
    }

    /**
     * Returns the message pattern
     *
     * @return  string
     */
    public function getPattern(): string
    {
        return '%u is an invalid UTF-8 code point';
    }
}

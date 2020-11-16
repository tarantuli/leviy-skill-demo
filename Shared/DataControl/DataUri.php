<?php
namespace Shared\DataControl;

use Shared\Exceptions\InvalidInputException;
use Shared\Http\Mimetype;

/**
 * Contains data URI methods
 */
class DataUri
{
    /*****************
     *   Constants   *
     ****************/

    public const REGEX = '#^data:([\w/.-]*);base64,([a-zA-Z0-9\+/]+)=*$#';


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  mixed  $argument
     *
     * @return  bool
     */
    public static function isDataUri($argument): bool
    {
        return (bool) preg_match(self::REGEX, $argument);
    }

    /**
     * Decodes a data URI. It returns the content and the MIME type
     *
     * @param  string  $argument  A data URI
     *
     * @return  array  [string,string]
     *
     * @throws  InvalidInputException
     */
    public static function decode(string $argument): array
    {
        if (!preg_match(self::REGEX, $argument, $match)) {
            throw new InvalidInputException($argument, 'data URI');
        }

        return [base64_decode($match[2]), $match[1]];
    }

    /**
     * Encodes a variable as a data URI
     *
     * @param  string       $argument  The variable to encode
     * @param  string|null  $type      The MIME type of the data
     *
     * @return  string
     */
    public static function encode(string $argument, string $type = null): string
    {
        if ($type === null) {
            $type = Mimetype::fromContent($argument);
        }

        return sprintf('data:%s;base64,%s', $type, base64_encode($argument));
    }
}

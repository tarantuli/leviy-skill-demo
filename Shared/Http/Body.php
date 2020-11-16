<?php
namespace Shared\Http;

use Shared\Exceptions\CaseNotImplementedException;
use Shared\Json\Json;

class Body
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string       $body
     * @param  string|null  $contentType
     *
     * @return  mixed
     */
    public static function parseFromString(string $body, ?string $contentType)
    {
        $contentType = self::stripCharset($contentType);

        switch ($contentType) {
            case 'application/json':
            case 'text/json':
                if (substr($body, 0, 1) === '$') {
                    $body = substr($body, 1);
                }

                return Json::decode($body);

            case 'application/x-www-form-urlencoded':
                parse_str($body, $parsedBody);

                return $parsedBody;

            default:
                return $body;
        }
    }

    /**
     * Turns the given content into a string based on the given content type
     *
     * @param  mixed        $body
     * @param  string|null  $contentType
     *
     * @return  string|null
     *
     * @throws  CaseNotImplementedException
     */
    public static function parseToString($body, ?string $contentType): ?string
    {
        $contentType = self::stripCharset($contentType);

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                return http_build_query($body);

            case 'text/json':
            case 'application/json':
                return Json::encode($body);

            case 'text/html':
            case null:
                return $body;
        }

        throw new CaseNotImplementedException($contentType);
    }

    /**
     * @param  string|null  $contentType
     *
     * @return  string|null
     */
    public static function stripCharset(?string $contentType): ?string
    {
        if (false !== $pos = strpos($contentType, ';')) {
            $contentType = substr($contentType, 0, $pos);
        }

        return $contentType;
    }
}

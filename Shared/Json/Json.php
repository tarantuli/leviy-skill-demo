<?php
namespace Shared\Json;

use Shared\DataControl\Regex;
use Shared\FileControl\Exceptions\FileNotFoundException;
use Shared\FileControl\File;

/**
 * This class contains JSON methods
 */
class Json
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string  $string
     *
     * @return  mixed
     *
     * @throws  Exceptions\JsonException
     */
    public static function decode(string $string)
    {
        $result = @ json_decode($string, JSON_OBJECT_AS_ARRAY);

        if ('No error' !== $error = json_last_error_msg()) {
            throw new Exceptions\JsonException($error);
        }

        return $result;
    }

    /**
     * @param  mixed  $content
     *
     * @return  string
     *
     * @throws  Exceptions\JsonException
     */
    public static function encode($content): string
    {
        $encoded = @ json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ('No error' !== $error = json_last_error_msg()) {
            throw new Exceptions\JsonException($error);
        }

        return $encoded;
    }

    /**
     * Encodes to JSON, then strips markers for human readability
     *
     * @param  mixed  $content
     *
     * @return  string
     */
    public static function encodeHumanReadable($content): string
    {
        return str_replace(
            ['\\\\', QUOTE, ',', ' [', ']', '{', '}'],
            ['\\', '', '', '', '', '', ''],
            static::encode($content)
        );
    }

    /**
     * @param  string  $filePath
     *
     * @return  mixed
     *
     * @throws  FileNotFoundException
     */
    public static function fromFile(string $filePath)
    {
        $filePath = File::normalizePath($filePath);

        if (!file_exists($filePath)) {
            throw new FileNotFoundException($filePath);
        }

        return static::decode(file_get_contents($filePath));
    }

    public static function fromObjectLiteral($literal)
    {
        $json = $literal;
        $json = str_replace(APOS, QUOTE, $json);

        Regex::replace('(?<=[\{,])(\w+):', '"$1":', $json);

        return $json;
    }

    /**
     * @param  mixed  $filePath
     * @param  mixed  $content
     *
     * @return  bool
     *
     * @throws  FileNotFoundException
     */
    public static function toFile($filePath, $content)
    {
        $filePath = File::normalizePath($filePath);

        if (!file_exists($filePath)) {
            throw new FileNotFoundException($filePath);
        }

        $encoded = static::encode($content);

        return (bool) file_put_contents($filePath, $encoded);
    }
}

<?php
namespace Shared\General;

use Closure;
use Shared\DataControl\Unicode\Unicode;

/**
 * This class contains callback methods, for example for preg_replace_callback()
 */
class ReturningClosure
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Applies the global function on the given argument. If the first parameter is
     * given, it is assumed to be the index of the argument to apply the function on
     *
     * Examples: ReturningClosure::mb_strtolower(0) will return a closure that
     * applies mb_strtolower on the first element of the given array. Usage in a
     * preg_replace_callback() to cast matched strings to lowecase:
     *
     * $code = preg_replace_callback('#\w+://\S+#',
     * ReturningClosure::mb_strtolower(0), $code);
     *
     * @param  string  $method
     * @param  array   $variables
     *
     * @return  Closure
     */
    public static function __callStatic(string $method, array $variables)
    {
        return function ($match) use ($method, $variables)
        {
            if (array_key_exists(0, $variables)) {
                return $method($match[$variables[0]]);
            }
            else {
                return $method($match);
            }
        };
    }

    /**
     * @return  Closure
     */
    public static function backSlashThenUcfirst()
    {
        return function ($m)
        {
            return BACKSLASH . ucfirst($m[1]);
        };
    }

    /**
     * Turns a character in a regex match array into a hexadecimal representation
     *
     * @return  Closure
     */
    public static function charToHexWrapper()
    {
        return function ($match)
        {
            return sprintf('\u%02u', dechex(ord($match[0])));
        };
    }

    public static function convertHtmlUnicodeToUtf8(): Closure
    {
        return function ($match)
        {
            return mb_convert_encoding($match[1], 'UTF-8', 'HTML-ENTITIES');
        };
    }

    public static function convertJsUnicodeToUtf8(): Closure
    {
        return function ($match)
        {
            return Unicode::codepointToUtf8Bytes(hexdec($match[1]));
        };
    }

    /**
     * @return  Closure
     */
    public static function dutchCapitalAfterApos()
    {
        return function ($m)
        {
            return sprintf('\'%s %s', mb_strtolower($m[1]), mb_strtoupper($m[2]));
        };
    }

    /**
     * Regex replace helper function that removes everything from the given string
     * except newlines
     *
     * @return  Closure
     */
    public static function removeEverythingButNewlines()
    {
        return function ($match)
        {
            return preg_replace('/[^' . LF . ']/', '', $match[0]);
        };
    }

    /**
     * @return  Closure
     */
    public static function repeatedLinesByNumber()
    {
        return function ($m)
        {
            return sprintf('%s (%u×)%s', trim($m[1]), strlen($m[0]) / strlen($m[1]), LF);
        };
    }

    /**
     * @return  Closure
     */
    public static function slashNormalizer()
    {
        return function ($path)
        {
            return iconv('ISO-8859-1', 'UTF-8', str_replace('\\', '/', $path));
        };
    }

    /**
     * @return  Closure
     */
    public static function startOfLineUpper()
    {
        return function ($m)
        {
            return mb_strtoupper($m[1]);
        };
    }

    /**
     * @return  Closure
     */
    public static function stripDots()
    {
        return function ($m)
        {
            return str_replace('.', '', $m[0]);
        };
    }

    /**
     * @return  Closure
     */
    public static function ucFirstSquareBrackets()
    {
        return function ($m)
        {
            return sprintf('[%s]', ucfirst(strtolower($m[1])));
        };
    }
}

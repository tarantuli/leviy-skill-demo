<?php
namespace Shared\DataControl;

/**
 * Contains string encoding methods
 */
class Encoding
{
    /*****************
     *   Constants   *
     ****************/

    public const ASCII = 'ASCII';
    public const ISO   = 'ISO-8859-1';
    public const UTF8  = 'UTF-8';


    /************************
     *   Static variables   *
     ***********************/

    private static $encodingList = [
        'UTF-8',       'ASCII',       'ISO-8859-1',  'ISO-8859-2',  'ISO-8859-3',  'ISO-8859-4',
        'ISO-8859-5',  'ISO-8859-6',  'ISO-8859-7',  'ISO-8859-8',  'ISO-8859-9',  'ISO-8859-10',
        'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
    ];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Converts the string to the given encoding
     *
     * @param  string  $string
     * @param  string  $toEncoding
     *
     * @return  string
     */
    public static function set(string $string, string $toEncoding): string
    {
        $currentEncoding = self::get($string);

        if ($currentEncoding == $toEncoding) {
            return $string;
        }

        return mb_convert_encoding($string, $toEncoding, $currentEncoding);
    }

    /**
     * Returns the encoding of the given string
     *
     * @param  string  $string
     *
     * @return  string
     */
    public static function get(string $string): string
    {
        $encoding = mb_detect_encoding($string, self::$encodingList, true);

        if ($encoding === 'ISO-8859-1' && preg_match('/[\x80-\x9f]/', $string)) {
            $encoding = 'Windows-1252';
        }

        return $encoding;
    }

    /**
     * Converts the string to ASCII encoding
     *
     * @param  string  $string
     *
     * @return  string
     */
    public static function toAscii(string $string): string
    {
        return self::set($string, self::ASCII);
    }

    /**
     * Converts the string to ISO-8859-1 encoding
     *
     * @param  string  $string
     *
     * @return  string
     */
    public static function toIso88591(string $string): string
    {
        return self::set($string, self::ISO);
    }

    /**
     * Converts the string to UTF-8 encoding
     *
     * @param  string  $string
     *
     * @return  string
     */
    public static function toUtf8(string $string): string
    {
        return self::set($string, self::UTF8);
    }

    /**
     * Converts the string to UTF-8 encoding, and attempts to fix broken strings
     *
     * @param  string  $string
     *
     * @return  string
     */
    public static function toUtf8WithFixing(string $string): string
    {
        if (mb_detect_encoding($string, 'UTF-8', true) === 'UTF-8') {
            return $string;
        }

        $encoding = self::get($string);

        if ($encoding !== false) {
            return mb_convert_encoding($string, 'UTF-8', $encoding);
        }
        else {
            return BadEncodingFixer::fixUTF8($string);
        }
    }
}

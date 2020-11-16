<?php
namespace Shared\Json;

use Shared\Functions;

class BinaryJson extends Json
{
    /*****************
     *   Constants   *
     ****************/

    /**
     * U+EB64 is a codepoint in Unicode's private use area (U+E000 - U+F8FF), the
     * 'B64' refers to the base64 encoding used :-)
     *
     * @const  string
     */
    public const MARKER = "\xee\xad\xa4";

    /**
     * @const  int
     */
    public const MARKER_LENGTH = 3;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string  $string
     *
     * @return  mixed
     */
    public static function decode(string $string)
    {
        $data = parent::decode($string);

        Functions::apply($data, [static::class, 'decodeBinaryData']);

        return $data;
    }

    /**
     * Decodes a string encoded by encodeBinaryData()
     *
     * @param  string  &$string  A reference to the string encoded by encodeBinaryData()
     *
     * @return  void
     */
    public static function decodeBinaryData(& $string)
    {
        if (substr($string, 0, self::MARKER_LENGTH) === self::MARKER) {
            $string = base64_decode(substr($string, self::MARKER_LENGTH));
        }
    }

    /**
     * @param  mixed  $data
     *
     * @return  string
     */
    public static function encode($data): string
    {
        Functions::apply($data, [static::class, 'encodeBinaryData']);

        return parent::encode($data);
    }

    /**
     * Encodes the referenced string using a private area UTF-8 character and base64
     * if it contains non-UTF-8 bytes
     *
     * @param  string  &$string  A reference to the string to encode
     *
     * @return  void
     */
    public static function encodeBinaryData(& $string)
    {
        if (!is_string($string)) {
            return;
        }

        if (mb_check_encoding($string, 'UTF-8')) {
            return;
        }

        $string = self::MARKER . base64_encode($string);
    }
}

<?php
namespace Shared\DataControl\Unicode;

/**
 * (summary missing)
 */
class Unicode
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns the one to four UTF-8 bytes of the given Unicode codepoint
     *
     * @param  int  $codepoint  The Unicode codepoint
     *
     * @return  string
     *
     * @throws  Exceptions\InvalidUtf8CodepointException
     */
    public static function codepointToUtf8Bytes(int $codepoint): string
    {
        if ($codepoint < 0) {
            throw new Exceptions\InvalidUtf8CodepointException($codepoint);
        }

        if ($codepoint <= 0x7f) {
            return chr($codepoint);
        }

        if ($codepoint <= 0x7ff) {
            return chr(($codepoint >> 6) + 192) . chr(($codepoint & 63) + 128);
        }

        if ($codepoint <= 0xffff) {
            return chr(($codepoint >> 12) + 224)
                . chr((($codepoint >> 6) & 63) + 128)
                . chr(($codepoint & 63) + 128);
        }

        if ($codepoint <= 0x1fffff) {
            return chr(($codepoint >> 18) + 240)
                . chr((($codepoint >> 12) & 63) + 128)
                . chr((($codepoint >> 6) & 63) + 128)
                . chr(($codepoint & 63) + 128);
        }

        throw new Exceptions\InvalidUtf8CodepointException($codepoint);
    }
}

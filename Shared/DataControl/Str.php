<?php
namespace Shared\DataControl;

use Shared\DataControl;
use Shared\Exceptions;
use Shared\General\ReturningClosure;

/**
 * (summary missing)
 */
class Str
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string  $string
     * @param  string  $interfix
     *
     * @return  bool
     */
    public static function contains(string $string, string $interfix): bool
    {
        return false !== strpos($string, $interfix);
    }

    /**
     * @param  string  $text
     *
     * @return  string
     */
    public static function convertHtmlUnicodeToUtf8(string $text): string
    {
        return preg_replace_callback('/(&#[0-9]+;)/', ReturningClosure::convertHtmlUnicodeToUtf8(), $text);
    }

    /**
     * @param  string  $text
     *
     * @return  string
     */
    public static function convertJsUnicodeToUtf8(string $text): string
    {
        return preg_replace_callback(
            '#\\\\u([a-f0-9]{4})#u',
            ReturningClosure::convertJsUnicodeToUtf8(),
            $text
        );
    }

    /**
     * @param  string  $string
     * @param  string  $encoding
     *
     * @return  string
     */
    public static function setEncoding(string $string, string $encoding): string
    {
        return Encoding::set($string, $encoding);
    }

    /**
     * @param  string  $string
     *
     * @return  string
     */
    public static function getEncoding(string $string): string
    {
        return Encoding::get($string);
    }

    /**
     * @param  string  $string
     * @param  string  $prefix
     *
     * @return  bool
     */
    public static function endsWith(string $string, string $prefix): bool
    {
        return substr($string, -strlen($prefix)) === $prefix;
    }

    /**
     * Returns the given string. If its length in characters is longer than the given
     * maximum, it is truncated on the *left size* and "…" is appended. Useful for
     * display purposes
     *
     * @param  string  $string
     * @param  int     $maxCharLength
     *
     * @return  string
     */
    public static function leftTruncateToCharLength(string $string, int $maxCharLength): string
    {
        return mb_strlen($string) <= $maxCharLength
            ? $string
            : '…' . mb_substr($string, -($maxCharLength - 1));
    }

    /**
     * Replaces variable names surrounded by double curly brackets ("{{name}}") by
     * the value in the associated arguments array
     *
     * @param  string  $pattern
     * @param  array   $arguments
     *
     * @return  string
     */
    public static function replaceVariables(string $pattern, array $arguments): string
    {
        foreach ($arguments as $name => $value) {
            $pattern = str_replace(sprintf('{{%s}}', $name), $value, $pattern);
        }

        return $pattern;
    }

    /**
     * Splits a string into chunks of a given length, unicode safe
     *
     * @param  string  $string
     * @param  int     $length
     *
     * @return  string[]
     */
    public static function splitIntoChunks(string $string, int $length): array
    {
        if ($length > 0) {
            $ret = [];
            $len = mb_strlen($string, 'UTF-8');

            for ($i = 0; $i < $len; $i += $length) {
                $ret[] = mb_substr($string, $i, $length, 'UTF-8');
            }

            return $ret;
        }

        return preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Turns a printf pattern and optional variables into a single string
     *
     * @param  string         $pattern
     * @param  array          $arguments
     * @param  callable|null  $stringProcessor      The callback to call to quote "s" arguments
     * @param  callable|null  $identifierProcessor  The callback to call to quote "q" arguments
     *
     * @return  string
     *
     * @throws  Exceptions\InvalidInputException
     */
    public static function sprintf(string $pattern, array $arguments, callable $stringProcessor = null, callable $identifierProcessor = null): string
    {
        if (!$arguments) {
            return $pattern;
        }

        if ($stringProcessor === null) {
            $stringProcessor = [DataControl\Variable::class, 'toString'];
        }

        if ($identifierProcessor === null) {
            $identifierProcessor = [DataControl\Variable::class, 'toString'];
        }

        $regex     = '/%(?:(\d+)\$)?[+-]?[ 0]?-?\d*([%bcdeufFloqsxX])/';
        $specCount = preg_match_all($regex, $pattern, $specs, PREG_SET_ORDER);

        // If no printf format specifiers were found, return immediately
        if ($specCount === 0) {
            return $pattern;
        }

        // Determine the types per argument, taking references of the format '%2$s' into account
        $types  = [];
        $specId = 1;

        foreach ($specs as $spec) {
            $type = $spec[2];

            if ($type === '%') {
                continue;
            }

            $ref = DataControl\Variable::keyval($spec, 1, '');

            if ($ref !== '') {
                $types[(int) $ref] = $type;
            }
            else {
                $types[$specId] = $type;

                ++$specId;
            }

            // Transform the custom specifiers 'l' and 'q' into a normal 's'
            if ($type === 'l' || $type === 'q') {
                $newSpec = sprintf('%ss', substr($spec[0], 0, -1));
                $pattern = str_replace($spec[0], $newSpec, $pattern);
            }
        }

        // Walk the types array, passing arguments through escape() if they're strings (type 's')
        $processedArguments = [];

        foreach ($arguments as $i => $arg) {
            $j    = $i + 1;
            $type = DataControl\Variable::keyval($types, $j);

            if ($type === 's' && $stringProcessor) {
                $arg = call_user_func($stringProcessor, $arg);
            }
            elseif ($type === 'q' && $identifierProcessor) {
                $arg = call_user_func($identifierProcessor, $arg);
            }

            $processedArguments[$j] = $arg;
        }

        $resultText = @ vsprintf($pattern, $processedArguments);

        if ($resultText === false) {
            throw new Exceptions\InvalidInputException(
                ['pattern' => $pattern, 'arguments' => $arguments],
                'combination of pattern and arguments'
            );
        }

        return $resultText;
    }

    /**
     * @param  string  $string
     * @param  string  $prefix
     *
     * @return  bool
     */
    public static function startsWith(string $string, string $prefix): bool
    {
        return substr($string, 0, strlen($prefix)) === $prefix;
    }

    /**
     * Strips diacriticals from the given string, replacing "é" by "e" for instance
     *
     * @param  string  $string
     *
     * @return  string
     */
    public static function stripDiacritics(string $string): string
    {
        return html_entity_decode(
            preg_replace(
                '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/ui',
                '$1',
                htmlentities($string)
            )
        );
    }

    /**
     * @param  string  $string
     *
     * @return  int
     */
    public static function toInt(string $string): int
    {
        $int = 0;

        for ($p = 0; $p < strlen($string); ++$p) {
            $int = ($int + (ord(substr($string, $p, 1)) * pow(255, $p))) % 9999999999999;
        }

        return $int;
    }

    /**
     * Returns the given string. If its length in bytes is longer than the given
     * maximum, it is truncated on a character level but to the given byte length,
     * and "…" is appended. Useful for storage purposes, while still remaining
     * valid UTF-8
     *
     * @param  string  $string
     * @param  int     $maxByteLength
     *
     * @return  string
     */
    public static function truncateToByteLength(string $string, int $maxByteLength): string
    {
        if (strlen($string) <= $maxByteLength) {
            return $string;
        }

        // First, cut by character length
        $cutByCharacters = mb_substr($string, 0, $maxByteLength - 1);

        // Then, pop off single characters at the end until its *length in bytes* is good
        while (strlen($cutByCharacters) > $maxByteLength - 3) {
            $cutByCharacters = mb_substr($cutByCharacters, 0, -1);
        }

        // Append the ellipsis (three bytes long)
        return $cutByCharacters . '…';
    }

    /**
     * Returns the given string. If its length in characters is longer than the given
     * maximum, it is truncated and "…" is appended. Useful for display purposes
     *
     * @param  string  $string
     * @param  int     $maxCharLength
     *
     * @return  string
     */
    public static function truncateToCharLength(string $string, int $maxCharLength): string
    {
        return mb_strlen($string) <= $maxCharLength
            ? $string
            : mb_substr($string, 0, $maxCharLength - 1) . '…';
    }
}

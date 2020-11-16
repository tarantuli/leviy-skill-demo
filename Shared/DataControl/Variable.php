<?php
namespace Shared\DataControl;

use ArrayAccess;
use Traversable;

/**
 * (summary missing)
 */
class Variable
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns whether the given value is accessible by array keys
     *
     * @param  mixed  $value
     *
     * @return  bool
     */
    public static function isArrayAccessible($value): bool
    {
        return is_array($value) || ($value instanceof ArrayAccess);
    }

    /**
     * Turns an array into a human-readable string representation
     *
     * @param  array  $argument   The array to transform
     * @param  int    $maxLength
     *
     * @return  string
     */
    public static function arrayToString(array $argument, int $maxLength = 255): string
    {
        if (count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)) >= 25) {
            return '[... snipped due to backtrace depth ...]';
        }

        $retval  = '[';
        $counter = 0;

        foreach ($argument as $key => $value) {
            if ($counter > 0) {
                $retval .= ', ';
            }

            if ((string) $key != (string) $counter) {
                $retval .= self::toString($key, $maxLength);
                $retval .= ': ';
            }

            $retval .= self::toString($value, $maxLength);

            ++$counter;
        }

        $retval .= ']';

        return $retval;
    }

    /**
     * Returns whether the given value is an integer value or string
     *
     * @param  mixed  $value
     *
     * @return  bool
     */
    public static function isIntval($value): bool
    {
        return is_int($value)
            || (is_string($value) && ctype_digit($value))
            || (is_float($value) && floor($value) == ceil($value));
    }

    /**
     * Returns whether the given array has the given key defined. It checks the types
     * of $array and $key as well
     *
     * @param  mixed       $array
     * @param  int|string  $key
     *
     * @return  bool
     */
    public static function hasKey($array, $key): bool
    {
        if (!is_int($key) && !is_string($key)) {
            return false;
        }

        if (is_array($array)) {
            return array_key_exists($key, $array);
        }

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return false;
    }

    /**
     * Returns the value in the array given by key, or the default value if it's not
     * an array or the key is not set
     *
     * @param  mixed             $array
     * @param  array|int|string  $key
     * @param  mixed             $default
     *
     * @return  mixed
     */
    public static function keyval($array, $key, $default = null)
    {
        if (is_array($key)) {
            // Look recursively for each key
            foreach ($key as $subkey) {
                $array = self::keyval($array, $subkey, $default);
            }

            return $array;
        }

        return self::hasKey($array, $key) ? $array[$key] : $default;
    }

    public static function limit($value, $min, $max)
    {
        return min(max($value, $min), $max);
    }

    /**
     * Returns whether the given array is numeric or not
     *
     * @param  array  &$array
     *
     * @return  bool
     */
    public static function isNumericArray(& $array): bool
    {
        if (!is_array($array)) {
            return false;
        }

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        for (reset($array); is_int(key($array)); next($array));

        return key($array) === null;
    }

    /**
     * If the argument is an instance, it's turned into an ID. Otherwise, the
     * argument is left as is
     *
     * @param  mixed  &$object  The object to change
     *
     * @return  void
     */
    public static function objectToId(& $object): void
    {
        if (!is_object($object)) {
            return;
        }

        $object = $object->id();
    }

    /**
     * Turns an object into a human-readable string representation
     *
     * @param  mixed  $argument   The object to transform
     * @param  int    $maxLength
     *
     * @return  string
     */
    public static function objectToString($argument, int $maxLength = 255): string
    {
        if (!is_object($argument)) {
            return $argument;
        }

        $retval      = get_class($argument);
        $retval     .= '(';
        $firstValue  = true;
        $vars        = get_object_vars($argument);

        if (method_exists($argument, 'id')) {
            $vars['id'] = $argument->id();
        }

        foreach ($vars as $key => $value) {
            if (!$firstValue) {
                $retval .= ', ';
            }

            $retval     .= self::toString($key, $maxLength);
            $retval     .= ': ';
            $retval     .= self::toString($value, $maxLength);
            $firstValue  = false;
        }

        $retval .= ')';

        return $retval;
    }

    /**
     * Turns the argument into a UTF-8 string with non-printable characters replaced
     * by a black square followed by the byte as integer
     *
     * @param  mixed  $argument            A scalar value
     * @param  mixed  $maxLength           The maximum length it should return
     * @param  bool   $collapseWhitespace
     *
     * @return  string
     */
    public static function toSafeString($argument, $maxLength = 65535, bool $collapseWhitespace = false): string
    {
        if (!is_string($argument)) {
            $argument = (string) $argument;
        }

        // Convert to UTF-8
        if (mb_detect_encoding($argument, 'UTF-8,ISO-8859-1', true) === 'ISO-8859-1') {
            $argument = iconv('ISO-8859-1', 'UTF-8', $argument);
        }

        if ($collapseWhitespace) {
            $argument = preg_replace('/\n+/m', '↵', trim($argument));
            $argument = preg_replace('/\s+/m', ' ', trim($argument));
        }

        $argument = preg_replace('/password: .+?([,\]])/', 'password: ****$1', $argument);

        if (strlen($argument) > $maxLength) {
            $argument = mb_strcut($argument, 0, $maxLength, 'UTF-8');
        }

        // Replace all non-printable characters by a black square followed by the byte as integer
        if (preg_match_all('/[\x00-\x1F\x7F]/u', $argument, $matches)) {
            $byteChars = array_unique($matches[0]);

            foreach ($byteChars as $byte) {
                $argument = str_replace($byte[0], self::encodeByteAsInt($byte[0]), $argument);
            }

            if (strlen($argument) > $maxLength) {
                $argument = mb_strcut($argument, 0, $maxLength, 'UTF-8');
            }
        }

        return $argument;
    }

    /**
     * Turns a variable into a string formatted for reading by humans, meant for log
     * files
     *
     * @param  mixed  $argument            The variable
     * @param  int    $maxLength           The maximum length of the string
     * @param  bool   $collapseWhitespace
     *
     * @return  string
     */
    public static function toString($argument, int $maxLength = 255, bool $collapseWhitespace = false): string
    {
        if (is_array($argument)) {
            $argument = self::arrayToString($argument, $maxLength);
        }
        elseif (is_object($argument)) {
            $argument = self::objectToString($argument, $maxLength);
        }
        elseif ($argument === null) {
            $argument = 'NULL';
        }
        elseif ($argument === true) {
            $argument = 'TRUE';
        }
        elseif ($argument === false) {
            $argument = 'FALSE';
        }
        elseif (is_resource($argument)) {
            $argument = sprintf('Resource:%s(%u)', get_resource_type($argument), (int) $argument);
        }
        elseif (!is_numeric($argument)) {
            $argument = self::toSafeString($argument, $maxLength, $collapseWhitespace);

            if ($argument == '' || preg_match('/\W/', $argument) && substr($argument, 0, 1) !== QUOTE) {
                $argument = sprintf('%s%s%s', QUOTE, $argument, QUOTE);
            }
        }

        if (strlen($argument) > $maxLength) {
            $argument = mb_strcut($argument, 0, $maxLength, 'UTF-8');
        }

        return $argument;
    }

    /**
     * Returns whether the given value is traversable using foreach()
     *
     * @param  mixed  $value
     *
     * @return  bool
     */
    public static function isTraversable($value): bool
    {
        return is_array($value) || ($value instanceof Traversable);
    }

    /**
     * Encodes a byte found by preg_match() as a marker plus the byte value as
     * integer
     *
     * @param  string  $byte  An array with the byte as the first value
     *
     * @return  string
     */
    private static function encodeByteAsInt(string $byte): string
    {
        return sprintf('▪%02u', ord($byte));
    }
}

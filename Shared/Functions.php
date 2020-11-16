<?php
namespace Shared;

/**
 * Contains loose uncategorized functions
 */
class Functions
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Applies the given method on the argument, recursively if needed
     *
     * @param  mixed     &$argument
     * @param  callable  $method
     *
     * @return  void
     */
    public static function apply(& $argument, callable $method)
    {
        if (is_scalar($argument)) {
            $method($argument);
        }
        elseif (is_array($argument)) {
            array_walk_recursive($argument, $method);
        }
    }

    /**
     * Clamps the given value within the given minimum and maximum value
     *
     * @param  int  $value
     * @param  int  $min
     * @param  int  $max
     *
     * @return  int
     */
    public static function clamp(int $value, int $min, int $max)
    {
        if ($value <= $min) {
            return $min;
        }

        if ($value >= $max) {
            return $max;
        }

        return $value;
    }
}

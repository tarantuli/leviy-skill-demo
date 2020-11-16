<?php
namespace Shared\System;

/**
 * (summary missing)
 */
class Info
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Checks whether the given function is callable and not disabled
     *
     * @param  string  $function
     *
     * @return  bool
     */
    public static function isFunctionAvailable(string $function)
    {
        return is_callable($function) && false === stripos(ini_get('disable_functions'), $function);
    }

    /**
     * Returns the memory limit in bytes
     *
     * @return  int
     */
    public static function getMemoryLimit()
    {
        static $limit;

        if ($limit === null) {
            $limit    = ini_get('memory_limit');
            $lastChar = strtolower(substr($limit, -1));

            if ($lastChar === 'g') {
                $limit = substr($limit, 0, -1) * GIGABYTE;
            }
            elseif ($lastChar === 'm') {
                $limit = substr($limit, 0, -1) * MEGABYTE;
            }
            elseif ($lastChar === 'k') {
                $limit = substr($limit, 0, -1) * KILOBYTE;
            }
        }

        return $limit;
    }
}

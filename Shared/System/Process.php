<?php
namespace Shared\System;

/**
 * (summary missing)
 */
class Process
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns whether the given class method appears more than once in the call
     * stack
     *
     * @return  bool
     */
    public static function areWeStuck()
    {
        $traceCalls = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $targetMethodCallCount = 0;
        $class      = null;
        $method     = null;

        foreach ($traceCalls as $call) {
            if (!array_key_exists('function', $call) || !array_key_exists('class', $call)) {
                continue;
            }

            if ($call['class'] === static::class && $call['function'] === __FUNCTION__) {
                continue;
            }

            if ($class === null) {
                // We found the source
                $class  = $call['class'];
                $method = $call['function'];
            }

            if ($call['class'] === $class && $call['function'] === $method) {
                ++$targetMethodCallCount;
            }

            // Return as soon as possible
            if ($targetMethodCallCount >= 2) {
                return true;
            }
        }

        return false;
    }
}

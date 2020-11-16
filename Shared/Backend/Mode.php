<?php
namespace Shared\Backend;

/**
 * (summary missing)
 */
class Mode
{
    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  bool
     */
    private static $inTestingMode = false;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Enter testing mode
     *
     * @return  bool|null
     */
    public static function enterTestingMode(): ?bool
    {
        if (self::$inTestingMode === true) {
            return null;
        }

        self::$inTestingMode = true;

        return true;
    }

    /**
     * Returns whether the backend is running in test mode
     *
     * @return  bool
     */
    public static function inTestingMode(): bool
    {
        return self::$inTestingMode;
    }
}

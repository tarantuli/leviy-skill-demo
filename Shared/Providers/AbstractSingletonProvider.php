<?php
namespace Shared\Providers;

abstract class AbstractSingletonProvider implements Interfaces\ProviderInterface
{
    /************************
     *   Static variables   *
     ***********************/

    private static $providers = [];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a singleton instance of this provider
     *
     * @return  static
     */
    public static function get()
    {
        if (!array_key_exists(static::class, self::$providers)) {
            self::$providers[static::class] = new static();
        }

        return self::$providers[static::class];
    }

    protected static function setInstance($instance): void
    {
        self::$providers[static::class] = $instance;
    }
}

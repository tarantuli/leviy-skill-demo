<?php
namespace Shared\Providers\Interfaces;

interface ProviderInterface
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a singleton instance of this provider
     *
     * @return  static
     */
    public static function get();
}

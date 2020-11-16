<?php
namespace Shared\Reflection;

use ReflectionClass;
use Shared\Exceptions\InterfaceNotImplementedException;

class Assert
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string  $class
     * @param  string  $interface
     *
     * @throws  InterfaceNotImplementedException
     */
    public static function implementation(string $class, string $interface): void
    {
        if ((new ReflectionClass($class))->implementsInterface($interface)) {
            return;
        }

        throw new InterfaceNotImplementedException($class, $interface);
    }
}

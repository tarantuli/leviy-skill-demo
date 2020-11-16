<?php
namespace Shared\DataControl\DataObjects\Interfaces;

use stdClass;

interface DataObjectInterface
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Turns the (response) array into an object of this (static) class
     *
     * @param  array  $data
     *
     * @return  static
     */
    public static function fromArray(array $data);

    /**
     * Turns the stdClass object into an object of this (static) class
     *
     * @param  stdClass  $instance
     *
     * @return  static
     */
    public static function fromStdClass(stdClass $instance);
}

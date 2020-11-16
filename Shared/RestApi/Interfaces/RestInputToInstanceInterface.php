<?php
namespace Shared\RestApi\Interfaces;

interface RestInputToInstanceInterface
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  mixed  $input
     *
     * @return  mixed
     */
    public static function castRestInputToInstance($input);
}

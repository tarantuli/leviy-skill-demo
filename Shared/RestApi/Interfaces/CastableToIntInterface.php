<?php
namespace Shared\RestApi\Interfaces;

interface CastableToIntInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  int|null
     */
    public function toRestResponseInt(): ?int;
}

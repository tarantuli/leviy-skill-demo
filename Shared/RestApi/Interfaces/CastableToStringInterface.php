<?php
namespace Shared\RestApi\Interfaces;

interface CastableToStringInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    public function toRestResponseString(): ?string;
}

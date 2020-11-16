<?php
namespace Shared\RestApi\Interfaces;

interface CastableToArrayInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    public function toRestResponseArray(): array;
}

<?php
namespace Shared\Authentication\Interfaces;

use Shared\Entities\Interfaces\EntityInterface;

interface FrontendInterface extends EntityInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  int
     */
    public function id(): int;

    /**
     * @return  string
     */
    public function getName(): string;
}

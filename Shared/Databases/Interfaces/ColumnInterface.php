<?php
namespace Shared\Databases\Interfaces;

interface ColumnInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  string|null
     */
    public function getDefinition(): ?string;
}

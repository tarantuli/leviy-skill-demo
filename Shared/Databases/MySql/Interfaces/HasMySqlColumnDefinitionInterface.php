<?php
namespace Shared\Databases\MySql\Interfaces;

interface HasMySqlColumnDefinitionInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Returns the MySQL column definition for this type
     *
     * @param  array  $property
     *
     * @return  string
     */
    public function getMySqlColumnDefinition(array $property): string;
}

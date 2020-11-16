<?php
namespace Shared\Types;

use Shared\Databases\MySql\Interfaces\HasMySqlColumnDefinitionInterface;
use Shared\Exceptions\InvalidInputException;

class FloatType extends Integer implements HasMySqlColumnDefinitionInterface
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
    public function getMySqlColumnDefinition(array $property): string
    {
        return 'float unsigned';
    }

    public function getName(): string
    {
        return 'float';
    }

    /**
     * @param  mixed  $value
     *
     * @return  float
     *
     * @throws  InvalidInputException
     */
    public function normalize($value)
    {
        if (!is_scalar($value)) {
            throw new InvalidInputException($value, 'scalar');
        }

        $firstDot   = strpos($value, '.');
        $firstComma = strpos($value, ',');

        if ($firstComma !== false && ($firstDot === false || $firstDot < $firstComma)) {
            $value = str_replace(',', '.', str_replace('.', '', $value));
        }

        return (float) $value;
    }

    public function getParamType(): string
    {
        return 'float';
    }
}

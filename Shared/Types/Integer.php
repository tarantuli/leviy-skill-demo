<?php
namespace Shared\Types;

use Shared\DataControl\Variable;
use Shared\Databases\MySql\Interfaces\HasMySqlColumnDefinitionInterface;
use Shared\Exceptions\InvalidInputException;

/**
 * (summary missing)
 */
class Integer extends AbstractBase implements HasMySqlColumnDefinitionInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    public function getAdditionalParameters(): array
    {
        return [
            new Parameters\TypeParameter('minValue', static::class, false),
            new Parameters\TypeParameter('maxValue', static::class, false),
        ];
    }

    /**
     * Returns the MySQL column definition for this type
     *
     * @param  array  $property
     *
     * @return  string
     *
     * @throws  InvalidInputException
     */
    public function getMySqlColumnDefinition(array $property): string
    {
        $minValue = Variable::keyval($property, 'minValue') ?: 0;
        $maxValue = Variable::keyval($property, 'maxValue') ?: 4294967295;

        if ($minValue >= 0 && $maxValue <= 255) {
            return 'tinyint unsigned';
        }

        if ($minValue >= -128 && $maxValue <= 127) {
            return 'tinyint';
        }

        if ($minValue >= 0 && $maxValue <= 65535) {
            return 'smallint unsigned';
        }

        if ($minValue >= -32768 && $maxValue <= 32767) {
            return 'smallint';
        }

        if ($minValue >= 0 && $maxValue <= 16777215) {
            return 'mediumint unsigned';
        }

        if ($minValue >= -8388608 && $maxValue <= 8388607) {
            return 'mediumint';
        }

        if ($minValue >= 0 && $maxValue <= 4294967295) {
            return 'int unsigned';
        }

        if ($minValue >= -2147483648 && $maxValue <= 2147483647) {
            return 'int';
        }

        if ($minValue >= 0 && $maxValue <= 18446744073709551615) {
            return 'bigint unsigned';
        }

        if ($minValue >= -9223372036854775808 && $maxValue <= 9223372036854775807) {
            return 'bigint';
        }

        throw new InvalidInputException($maxValue, 'maximum value');
    }

    public function getName(): string
    {
        return 'integer';
    }

    /**
     * @param  mixed  $value
     *
     * @return  int
     *
     * @throws  InvalidInputException
     */
    public function normalize($value)
    {
        if (!is_scalar($value)) {
            throw new InvalidInputException($value, 'scalar');
        }

        return (int) $value;
    }

    public function getParamType(): string
    {
        return 'int';
    }

    public function unserialize(?string $value)
    {
        return $value === null ? null : (int) $value;
    }

    public function validate($value, array $parameters = []): bool
    {
        if (array_key_exists('allowNull', $parameters) && $parameters['allowNull'] && $value === null) {
            return true;
        }

        return $value >= Variable::keyval($parameters, 'minValue', 0)
            && $value <= Variable::keyval($parameters, 'maxValue', PHP_INT_MAX);
    }
}

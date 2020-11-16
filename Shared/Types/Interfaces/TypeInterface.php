<?php
namespace Shared\Types\Interfaces;

use Shared\Types\Parameters\TypeParameter;

/**
 * (summary missing)
 */
interface TypeInterface
{
    /**********************
     *   Static methods   *
     *********************/

    public static function get();


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Returns the additional parameters needed for this type
     *
     * @return  TypeParameter[]
     */
    public function getAdditionalParameters(): array;

    /**
     * Returns the name of this type
     *
     * @return  string
     */
    public function getName(): string;

    /**
     * Normalizes the given value
     *
     * @param  mixed  $value
     *
     * @return  mixed
     */
    public function normalize($value);

    /**
     * Normalizes a value by reference, and returns whether it is valid
     *
     * @param  mixed  &$value
     * @param  array  $parameters
     *
     * @return  bool
     */
    public function normalizeAndValidate(& $value, array $parameters = []): bool;

    /**
     * Returns the parameter name for doccomments
     *
     * @return  string
     */
    public function getParamType(): string;

    /**
     * Serializes a value for database insertion
     *
     * @param  mixed  $value
     *
     * @return  string
     */
    public function serialize($value): ?string;

    /**
     * Unserializes a value after database retrieval
     *
     * @param  string|null  $value
     *
     * @return  mixed
     */
    public function unserialize(?string $value);

    /**
     * Validates the given value with the given parameters
     *
     * @param  mixed  $value
     * @param  array  $parameters
     *
     * @return  bool
     */
    public function validate($value, array $parameters = []): bool;
}

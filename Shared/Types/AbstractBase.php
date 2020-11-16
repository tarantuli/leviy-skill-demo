<?php
namespace Shared\Types;

/**
 * (summary missing)
 */
abstract class AbstractBase implements Interfaces\TypeInterface
{
    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  Interfaces\TypeInterface[]
     */
    private static $instances = [];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @return  static
     */
    public static function get()
    {
        if (!array_key_exists(static::class, self::$instances)) {
            self::$instances[static::class] = new static();
        }

        return self::$instances[static::class];
    }


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Normalizes the value by reference, then returns whether this normalized value
     * is valid
     *
     * @param  mixed  $value
     * @param  array  $parameters
     *
     * @return  bool
     */
    public function normalizeAndValidate(& $value, array $parameters = []): bool
    {
        $originalValue = $value;
        $value = $value === null ? null : $this->normalize($value);

        if ($this->validate($value, $parameters)) {
            return true;
        }
        else {
            $value = $originalValue;

            return false;
        }
    }

    /**
     * @param  mixed  $value
     *
     * @return  string
     */
    public function serialize($value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * @param  string|null  $value
     *
     * @return  mixed
     */
    public function unserialize(?string $value)
    {
        return $value;
    }
}

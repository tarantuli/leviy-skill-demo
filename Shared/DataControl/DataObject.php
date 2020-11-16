<?php
namespace Shared\DataControl;

use Shared\Exceptions\InvalidInputException;

class DataObject
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Changes the value of an array by using "root/branch/leaf" notation
     *
     * @param  array   $array      Array to traverse
     * @param  string  $path       Path to a specific option to extract
     * @param  mixed   $newValue
     * @param  string  $delimiter
     *
     * @return  array
     *
     * @throws  InvalidInputException
     */
    public static function setPathValue(array $array, string $path, $newValue, string $delimiter = '/'): array
    {
        // Fail if the path is empty
        if (empty($path)) {
            throw new InvalidInputException($path, 'path');
        }

        // Remove all leading and trailing slashes
        $path = trim($path, $delimiter);

        // Use current array as the initial value
        $value =& $array;

        // Extract parts of the path
        $parts = explode($delimiter, $path);

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                $value[$part] = null;
            }

            $value =& $value[$part];
        }

        $value = $newValue;

        return $array;
    }

    /**
     * Get value of an array by using "root/branch/leaf" notation
     *
     * Source:
     * http://codeaid.net/php/get-values-of-multi-dimensional-arrays-using-xpath-notation
     * (2015-11-12)
     *
     * @param  array   $array      Array to traverse
     * @param  string  $path       Path to a specific option to extract
     * @param  mixed   $default    Value to use if the path was not found
     * @param  string  $delimiter
     *
     * @return  mixed
     *
     * @throws  InvalidInputException
     */
    public static function getPathValue(array $array, string $path, $default = null, string $delimiter = '/')
    {
        // Fail if the path is empty
        if (empty($path)) {
            throw new InvalidInputException($path, 'path');
        }

        // Remove all leading and trailing slashes
        $path = trim($path, $delimiter);

        // Use current array as the initial value
        $value = $array;

        // Extract parts of the path
        $parts = explode($delimiter, $path);

        // Loop through each part and extract its value
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                // Replace current value with the child
                $value = $value[$part];
            }
            else {
                // Key doesn't exist, fail
                return $default;
            }
        }

        return $value;
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    protected $numberOfChanges = 0;

    /**
     * @var  array
     */
    private $data;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * DataObject constructor
     *
     * @param  array  $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Returns the data
     *
     * @return  array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param  string  $path
     * @param  mixed   $newValue
     *
     * @return  void
     */
    public function addToValue(string $path, $newValue): void
    {
        $values   = $this->getValue($path, []);
        $values[] = $newValue;

        $this->setValue($path, $values);
    }

    /**
     * Sets a value by path
     *
     * @param  string  $path
     * @param  mixed   $value
     *
     * @return  void
     */
    public function setValue(string $path, $value): void
    {
        $this->data = self::setPathValue($this->data, $path, $value);

        $this->increaseNumberOfChange();
    }

    /**
     * Returns a value by path
     *
     * @param  string  $path
     * @param  mixed   $default
     *
     * @return  mixed
     */
    public function getValue(string $path, $default = null)
    {
        return self::getPathValue($this->data, $path, $default);
    }

    /**
     * @param  array  $path
     * @param  mixed  $value
     */
    public function setValueByArray(array $path, $value)
    {
        $this->setValue(implode(SLASH, $path), $value);
    }

    /**
     * @param  array  $path
     * @param  mixed  $default
     *
     * @return  mixed
     */
    public function getValueByArray(array $path, $default = null)
    {
        return $this->getValue(implode(SLASH, $path), $default);
    }

    /**
     * Changes the data
     *
     * @param  array  $data
     * @param  bool   $triggerChange
     *
     * @return  void
     */
    protected function setData(array $data, bool $triggerChange = true): void
    {
        $this->data = $data;

        if ($triggerChange) {
            $this->increaseNumberOfChange();
        }
    }

    protected function increaseNumberOfChange()
    {
        ++$this->numberOfChanges;
    }
}

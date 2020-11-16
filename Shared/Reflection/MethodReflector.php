<?php
namespace Shared\Reflection;

use Exception;
use ReflectionMethod;
use ReflectionParameter;
use Shared\Exceptions\InvalidInputException;

/**
 * (summary missing)
 */
class MethodReflector
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns the name of the given ReflectionParameter
     *
     * @param  ReflectionParameter  $parameter
     *
     * @return  string
     */
    public static function getArgName(ReflectionParameter $parameter)
    {
        return $parameter->name;
    }

    /**
     * Returns the MethodReflector for the given class and method
     *
     * @param  mixed   $class
     * @param  string  $method
     *
     * @return  MethodReflector
     *
     * @throws  InvalidInputException
     */
    public static function forMethod($class, string $method)
    {
        if (!method_exists($class, $method)) {
            throw new InvalidInputException([$class, $method], 'class and method combination');
        }

        return new self($class, $method);
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  string
     */
    private $class;

    /**
     * @var  string
     */
    private $method;

    /**
     * @var  ReflectionMethod
     */
    private $reflector;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new instance of this class
     *
     * @param  mixed   $class
     * @param  string  $method
     */
    public function __construct($class, string $method)
    {
        $this->class     = $class;
        $this->method    = $method;
        $this->reflector = new ReflectionMethod($class, $method);
    }

    /**
     * Returns the default value of the given param
     *
     * @param  string  $param
     *
     * @return  mixed
     */
    public function getDefaultParameterValue(string $param)
    {
        $paramReflector = $this->getParameterReflector($param);

        return $paramReflector->getDefaultValue();
    }

    /**
     * Returns the number of required parameters
     *
     * @return  int
     */
    public function getNumberOfRequiredParameters()
    {
        return $this->reflector->getNumberOfRequiredParameters();
    }

    /**
     * Returns the parameter names of this method
     *
     * @return  string[]
     */
    public function getParameterNames()
    {
        try
        {
            return array_map('self::getArgName', $this->getParameters());
        }
        catch (Exception $e)
        {
            return [];
        }
    }

    /**
     * Returns a parameter reflection object for this parameter
     *
     * @param  string  $param
     *
     * @return  ReflectionParameter
     *
     * @throws  InvalidInputException
     */
    public function getParameterReflector(string $param)
    {
        $paramReflectors = $this->getParameters();

        foreach ($paramReflectors as $paramReflector) {
            if ($paramReflector->name == $param) {
                return $paramReflector;
            }
        }

        throw new InvalidInputException($param, 'parameter name');
    }

    /**
     * Returns whether the given parameter is required
     *
     * @param  string  $param
     *
     * @return  bool
     */
    public function isParameterRequired(string $param)
    {
        $paramReflector = $this->getParameterReflector($param);

        return !$paramReflector->isOptional();
    }

    /**
     * Returns the parameters of this method
     *
     * @return  ReflectionParameter[]
     */
    public function getParameters()
    {
        return $this->reflector->getParameters();
    }
}

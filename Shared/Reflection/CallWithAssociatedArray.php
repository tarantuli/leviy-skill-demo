<?php
namespace Shared\Reflection;

use Shared\DataControl\DataObjects\Interfaces\DataObjectInterface;
use Shared\DataControl\Variable;
use Shared\Exceptions\MissingArgumentException;
use Shared\Providers\ProviderRegistry;

/**
 * (summary missing)
 */
class CallWithAssociatedArray
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Calls the given class method by combining the given associated array of values
     * with the defaults
     *
     * @param  mixed   $class
     * @param  string  $method
     * @param  array   $givenValues
     *
     * @return  mixed
     */
    public static function call($class, string $method, array $givenValues)
    {
        $arguments = self::checkArguments($class, $method, $givenValues);

        return call_user_func_array([$class, $method], $arguments);
    }

    /**
     * @param  mixed   $class
     * @param  string  $method
     * @param  array   $givenValues
     *
     * @return  array
     *
     * @throws  MissingArgumentException
     */
    private static function checkArguments($class, string $method, array $givenValues): array
    {
        $arguments = [];
        $methodReflector = MethodReflector::forMethod($class, $method);

        foreach ($methodReflector->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                $argument = Variable::keyval($givenValues, $parameter->name, $parameter->getDefaultValue());
            }
            else {
                if (!Variable::hasKey($givenValues, $parameter->name)) {
                    throw new MissingArgumentException($parameter->name, $givenValues);
                }

                $argument = Variable::keyval($givenValues, $parameter->name);
            }

            if ($paraClass = $parameter->getClass()) {
                $paraClassName = $paraClass->name;

                if (is_array($argument)
                        && isset($argument['href'])
                        && $argument['href'] instanceof $paraClassName)
                {
                    $argument = $argument['href'];
                }
                elseif (Variable::isIntval($argument)
                        and $provider = ProviderRegistry::get()->forEntityClass($paraClassName))
                {
                    $argument = $provider->getInstance($argument);
                }
                elseif (in_array(DataObjectInterface::class, class_implements($paraClassName))) {
                    /**
                     * @var  DataObjectInterface  $paraClassName
                     */
                    $argument = $paraClassName::fromArray($argument);
                }
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }
}

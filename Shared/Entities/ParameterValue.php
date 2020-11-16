<?php
namespace Shared\Entities;

use ReflectionMethod;
use ReflectionParameter;
use Shared\DataControl\Variable;
use Shared\FileControl\File;
use Shared\Providers\ProviderRegistry;
use Shared\RestApi\Interfaces\RestInputToInstanceInterface;
use Shared\Types\File as TypesFile;
use Shared\Types\FloatType;

class ParameterValue
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  mixed        $value
     * @param  mixed        $instance
     * @param  string       $method
     * @param  string|null  $key
     *
     * @throws  Exceptions\ParameterNotFoundException
     */
    public static function normalize(& $value, $instance, string $method, string $key = null): void
    {
        $reflector  = new ReflectionMethod($instance, $method);
        $parameters = $reflector->getParameters();

        if ($key === null) {
            $parameter = $parameters[0];
        }
        else {
            if (!$parameter = self::getParameterWithName($parameters, $key)) {
                throw new Exceptions\ParameterNotFoundException($method, $key);
            }
        }

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        if ($type = $parameter->getType() and $typeName = $type->getName()) {
            if ($typeName === 'float') {
                $value = FloatType::get()->normalize($value);
            }
            elseif ($typeName === File::class) {
                $value = TypesFile::get()->normalize($value);
            }
        }

        if (!$parameterClass = $parameter->getClass()) {
            return;
        }

        $class = $parameterClass->getName();
        $classImplements = class_implements($class);

        if (in_array(RestInputToInstanceInterface::class, $classImplements)) {
            /** @noinspection PhpUndefinedMethodInspection */
            $value = $class::castRestInputToInstance($value);

            return;
        }

        if (in_array(Interfaces\EntityInterface::class, $classImplements)) {
            if (!Variable::isIntval($value)) {
                return;
            }

            $provider = ProviderRegistry::get()->forEntityClass($class);
            $value    = $provider->getInstance($value);

            return;
        }
    }

    /**
     * @param  ReflectionParameter[]  $parameters
     * @param  string                 $name
     *
     * @return  ReflectionParameter|null
     */
    private static function getParameterWithName(array $parameters, string $name): ?ReflectionParameter
    {
        foreach ($parameters as $parameter) {
            if ($parameter->name === $name) {
                return $parameter;
            }
        }

        return null;
    }
}

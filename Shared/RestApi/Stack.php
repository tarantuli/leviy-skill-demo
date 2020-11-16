<?php
namespace Shared\RestApi;

use Exception;
use Shared\DataControl\IdentifierCases;
use Shared\DataControl\Variable;
use Shared\Databases\Filter;
use Shared\Entities\Collection;
use Shared\Entities\Interfaces\EntityProviderInterface;
use Shared\Entities\ParameterValue;
use Shared\Exceptions\InvalidInputException;
use Shared\Providers\Interfaces\ProviderInterface;
use Shared\Reflection\Assert;
use Shared\Reflection\CallWithAssociatedArray;
use Shared\Reflection\DoccommentReflector;

/**
 * Maintains the state of the entity stack while parsing the REST resource path
 */
class Stack
{
    /*****************
     *   Constants   *
     ****************/

    // Stack type constants
    public const STACK_TYPE_CLASS    = 1;
    public const STACK_TYPE_ENTITIES = 2;
    public const STACK_TYPE_MIXED    = 3;

    // Return type constants
    public const RETURN_AS_IS      = 2;
    public const RETURN_ONE_ENTITY = 1;

    private const STACK_TYPE_NAMES = [
        self::STACK_TYPE_CLASS    => 'class',
        self::STACK_TYPE_ENTITIES => 'entity collection',
        self::STACK_TYPE_MIXED    => 'mixed',
    ];


    /************************
     *   Static variables   *
     ***********************/

    /**
     * Whether to apply max rows to the end result. May be turned off by calling
     * self::dontApplyMaxRows()
     *
     * @var  bool
     */
    private static $doApplyMaxRows = true;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Don't apply max rows to the end results; we've handled this elsewhere in the
     * request handling
     */
    public static function dontApplyMaxRows()
    {
        self::$doApplyMaxRows = false;
    }

    /**
     * @param  mixed   $class
     * @param  string  $method
     *
     * @return  string
     *
     * @throws  InvalidInputException
     */
    public static function findGetterMethod($class, string $method): string
    {
        if ($method === '') {
            $method = 'ActiveInstances';
        }

        if (method_exists($class, $method)) {
            $doccomment = DoccommentReflector::forMethod($class, $method, DoccommentReflector::RETURN_OBJECT);

            if ($doccomment->hasKeyword('isGetter')) {
                return $method;
            }
        }

        $prefixedMethod = IdentifierCases::toCamelCase('get ' . strtr($method, '-_', '  '));

        if (!is_callable([$class, $prefixedMethod])) {
            throw new InvalidInputException(
                $prefixedMethod,
                sprintf('method of class %s', is_object($class) ? get_class($class) : $class)
            );
        }

        return $prefixedMethod;
    }

    /**
     * Returns whether the given array of filters contains a filter on the state
     * field
     *
     * @param  array  $filters
     *
     * @return  bool
     */
    private static function containsStateFilter(array $filters): bool
    {
        foreach ($filters as $filter) {
            if (array_key_exists(1, $filter) && $filter[1] === 'state') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string  $method
     * @param  mixed   $instance
     *
     * @return  string
     *
     * @throws  InvalidInputException
     */
    private static function findAddMethod(string $method, $instance): string
    {
        if (!is_callable([$instance, $method])) {
            $newMethod = sprintf('add%s', ucfirst($method));

            if (!is_callable([$instance, $newMethod])) {
                throw new InvalidInputException(
                    $method,
                    sprintf('method of class %s (nor add...)', get_class($instance))
                );
            }

            $method = $newMethod;
        }

        return $method;
    }

    /**
     * @param  string  $method
     * @param  mixed   $class
     *
     * @return  string
     *
     * @throws  InvalidInputException
     */
    private static function findCreateMethod(string $method, $class): string
    {
        if (!is_callable([$class, $method])) {
            $newMethod = sprintf('create%s', ucfirst($method));

            if (!is_callable([$class, $newMethod])) {
                throw new InvalidInputException(
                    $method,
                    sprintf('method of class %s (nor create...)', $class)
                );
            }

            $method = $newMethod;
        }

        return $method;
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  string
     */
    private $class;

    /**
     * @var  RequestProcessor
     */
    private $request;

    /**
     * @var  array
     */
    private $requestFilters;

    /**
     * @var  int
     */
    private $requestLimit;

    /**
     * @var  int
     */
    private $requestOffset;

    /**
     * @var  int
     */
    private $returnType = self::RETURN_ONE_ENTITY;

    /**
     * @var  array
     */
    private $stack = [];

    /**
     * @var  int
     */
    private $stackType = self::STACK_TYPE_CLASS;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  RequestProcessor  $request
     * @param  string            $class
     */
    public function __construct(RequestProcessor $request, string $class)
    {
        $this->request = $request;
        $this->class   = $class;
    }

    /**
     * @param  mixed  $processor
     *
     * @return  void
     *
     * @throws  InvalidInputException
     * @throws  Exception
     */
    public function applyDeleteProcessor($processor): void
    {
        if (Variable::isIntval($processor) && $processor >= 1) {
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:
                    $this->applyGetInstanceOnClass($processor);

                    $this->deleteInstance();

                    return;

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply DELETE instance on');
            }
        }
        else {
            throw new InvalidInputException($processor, 'instance ID');
        }
    }

    /**
     * @param  string|null  $processor
     * @param  string       $requestMethod
     * @param  array        $requestParameters
     * @param  mixed        $requestBody
     *
     * @return  void
     *
     * @throws  InvalidInputException
     */
    public function applyFinalProcessor(?string $processor, string $requestMethod, array $requestParameters, $requestBody): void
    {
        switch ($requestMethod) {
            case 'GET':
                $this->applyGetProcessor($processor, $requestParameters);

                return;

            case 'POST':
                $this->applyPostProcessor($processor, $requestBody);

                return;

            case 'PUT':
                $this->applyPutProcessor($processor, $requestBody);

                return;

            case 'DELETE':
                $this->applyDeleteProcessor($processor);

                return;

            default:
                throw new InvalidInputException($requestMethod, 'request method');
        }
    }

    /**
     * @param  string  $processor
     *
     * @return  void
     *
     * @throws  InvalidInputException
     * @throws  Exception
     */
    public function applySecondaryProcessor(string $processor): void
    {
        if (Variable::isIntval($processor)) {
            // Get instance by ID
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:
                    $this->applyGetInstanceOnClass($processor);

                    return;

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply GET instance on');
            }
        }
        elseif (preg_match('#^\[(.+)]$#', $processor, $match)) {
            // Get instance by name
            $name = $match[1];

            foreach ($this->stack as $item) {
                if ($item->getName() === $name) {
                    $this->stack = $item;

                    return;
                }
            }

            throw new InvalidInputException($name, 'name of an instance');
        }
        else {
            switch ($this->stackType) {
                case self::STACK_TYPE_ENTITIES:
                    if (count($this->stack) == 1) {
                        $getterMethod     = self::findGetterMethod($this->stack[0], $processor);
                        $this->stack      = $this->stack[0]->$getterMethod();
                        $this->returnType = self::RETURN_AS_IS;

                        return;
                    }

                    throw new InvalidInputException($this->stack, 'stack of one instance');

                default:
                    throw new InvalidInputException(
                        self::STACK_TYPE_NAMES[$this->stackType],
                        'type to apply GET processor on'
                    );
            }
        }
    }

    /**
     * @param  array  $filters
     */
    public function setRequestFilters(array $filters): void
    {
        $this->requestFilters = $filters;
    }

    /**
     * @param  int|null  $limit
     */
    public function setRequestLimit(?int $limit): void
    {
        $this->requestLimit = $limit;
    }

    /**
     * @param  int|null  $offset
     */
    public function setRequestOffset(?int $offset): void
    {
        $this->requestOffset = $offset;
    }

    /**
     * @return  mixed
     *
     * @throws  InvalidInputException
     */
    public function getReturnValue()
    {
        switch ($this->returnType) {
            case self::RETURN_ONE_ENTITY:
                return $this->returnOneEntity();

            case self::RETURN_AS_IS:
                return $this->stack;

            default:
                throw new InvalidInputException($this->returnType, 'return type');
        }
    }

    /**
     * @param  int  $id
     *
     * @return  void
     *
     * @throws  InvalidInputException
     * @throws  Exception
     */
    private function applyGetInstanceOnClass(int $id): void
    {
        $class = $this->class;

        Assert::implementation($class, EntityProviderInterface::class);

        /**
         * @var  EntityProviderInterface  $class
         */
        $provider = $class::get();

        // Get an instance
        $this->stackType = self::STACK_TYPE_ENTITIES;

        if (!$instance = $provider->getInstance($id)) {
            throw new InvalidInputException($id, sprintf('ID of class %s', $class));
        }

        if ($filters = $provider->getReadFiltersForCaller()) {
            $filters[] = Filter::isEqual('id', $id);

            if (count($provider->getInstances($filters)) == 0) {
                throw new InvalidInputException($id, 'instance readable by caller');
            }
        }

        $this->stack      = [$instance];
        $this->returnType = self::RETURN_ONE_ENTITY;
    }

    /**
     * @return  void
     */
    private function applyGetInstancesOnClass(): void
    {
        $providerClass = $this->class;

        Assert::implementation($providerClass, EntityProviderInterface::class);

        /**
         * @var  EntityProviderInterface  $providerClass
         */
        $provider = $providerClass::get();

        // Get all active instance
        $this->stackType = self::STACK_TYPE_ENTITIES;
        $this->returnType = self::RETURN_AS_IS;
        $filters   = $provider->getReadFiltersForCaller();
        $afterSort = null;

        foreach ($this->requestFilters as $restFilter) {
            if ($restFilter[0] === 'aftersort') {
                $afterSort = array_pop($restFilter[2]);
            }
            else {
                $filters[] = RestFunctions::restFilterToDatastoreFilter($restFilter);
            }
        }

        $filters[] = Filter::maxRowCount($this->requestLimit);
        $filters[] = Filter::rowOffset($this->requestOffset);

        if (self::containsStateFilter($filters)) {
            $this->stack = $provider->getInstances($filters);
        }
        else {
            $this->stack = $provider->getActiveInstances($filters);
        }

        $this->request->setCollectionMaxRows($provider->getActiveInstanceCount());

        if ($afterSort) {
            Collection::multisort($this->stack, Collection::multisortStringToMethodArray($afterSort));
        }
    }

    /**
     * @param  string  $method
     * @param  array   $requestParameters
     *
     * @return  void
     */
    private function applyGetMethodOnClass(string $method, array $requestParameters): void
    {
        $class  = $this->class;
        $method = self::findGetterMethod($class, $method);

        // Get an instance
        $this->stackType  = self::STACK_TYPE_ENTITIES;
        $this->returnType = self::RETURN_AS_IS;

        /**
         * @var  EntityProviderInterface  $class
         */
        $provider    = $class::get();
        $this->stack = CallWithAssociatedArray::call($provider, $method, $requestParameters);

        if ($this->requestLimit && is_array($this->stack) && self::$doApplyMaxRows) {
            $this->request->setCollectionMaxRows(count($this->stack));

            $this->stack = array_slice($this->stack, $this->requestOffset, $this->requestLimit);
        }
    }

    /**
     * @param  string|null  $processor
     * @param  array        $requestParameters
     *
     * @return  void
     *
     * @throws  InvalidInputException
     */
    private function applyGetProcessor(?string $processor, array $requestParameters): void
    {
        if (Variable::isIntval($processor)) {
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:
                    $this->applyGetInstanceOnClass($processor);

                    return;

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply GET instance on');
            }
        }
        elseif ($processor === null || $processor === '') {
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:
                    $this->applyGetInstancesOnClass();

                    return;

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply GET instances on');
            }
        }
        else {
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:
                    $this->applyGetMethodOnClass($processor, $requestParameters);

                    return;

                case self::STACK_TYPE_ENTITIES:
                    if (count($this->stack) == 1) {
                        $instance     = $this->stack[0];
                        $getterMethod = self::findGetterMethod($instance, $processor);

                        $this->stack = CallWithAssociatedArray::call(
                            $instance,
                            $getterMethod,
                            $requestParameters
                        );

                        $this->returnType = self::RETURN_AS_IS;

                        return;
                    }

                    throw new InvalidInputException($this->stack, 'stack of one instance');

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply GET method on');
            }
        }
    }

    /**
     * @param  string  $method
     * @param  array   $requestBody
     *
     * @return  void
     */
    private function applyPostMethodOnClass(string $method, array $requestBody): void
    {
        $class  = $this->class;
        $method = self::findCreateMethod($method, $class);

        /**
         * @var  ProviderInterface  $class
         */
        $provider         = $class::get();
        $this->stack      = CallWithAssociatedArray::call($provider, $method, $requestBody);
        $this->stackType  = self::STACK_TYPE_MIXED;
        $this->returnType = self::RETURN_AS_IS;

        if ($this->stack instanceof Interfaces\ResourceInterface) {
            if (Variable::keyval($requestBody, 'markSeen') && method_exists($this->stack, 'markSeen')) {
                $this->stack->markSeen();
            }

            $this->stack      = [$this->stack];
            $this->stackType  = self::STACK_TYPE_ENTITIES;
            $this->returnType = self::RETURN_ONE_ENTITY;
            $propertyValues   = Variable::hasKey($requestBody, 'data') ? $requestBody['data'] : $requestBody;

            $this->applySetPropertiesOnInstance($propertyValues);
        }
    }

    /**
     * @param  string  $method
     * @param  array   $requestBody
     *
     * @return  void
     */
    private function applyPostMethodOnEntity(string $method, array $requestBody): void
    {
        if (!$instance = Variable::keyval($this->stack, 0)) {
            return;
        }

        $method    = self::findAddMethod($method, $instance);
        $this->returnType = self::RETURN_AS_IS;
        $arguments = [];

        foreach ($requestBody as $key => $value) {
            ParameterValue::normalize($value, $instance, $method, $key);

            $arguments[$key] = $value;
        }

        $this->stack = CallWithAssociatedArray::call($instance, $method, $arguments);
    }

    /**
     * @param  string|null  $processor
     * @param  array        $requestBody
     *
     * @return  void
     *
     * @throws  InvalidInputException
     */
    private function applyPostProcessor(?string $processor, array $requestBody): void
    {
        if (!$processor) {
            $processor   = 'createInstance';
            $requestBody = ['data' => $requestBody];
        }

        if (!is_array($requestBody)) {
            $requestBody = [$requestBody];
        }

        if (!Variable::isIntval($processor)) {
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:
                    $this->applyPostMethodOnClass($processor, $requestBody);

                    return;

                case self::STACK_TYPE_ENTITIES:
                    $this->applyPostMethodOnEntity($processor, $requestBody);

                    return;

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply POST method on');
            }
        }
        else {
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:

                    /**
                     * @var  EntityProviderInterface  $class
                     */
                    $class    = $this->class;
                    $provider = $class::get();
                    $instance = $provider->getInstance($processor);
                    $addedProperties = 0;

                    foreach ($requestBody as $key => $value) {
                        $method = sprintf('add%s', ucfirst($key));

                        ParameterValue::normalize($value, $instance, $method);

                        $addedProperties += CallWithAssociatedArray::call($instance, $method, [$key => $value]);
                    }

                    $this->returnType = self::RETURN_AS_IS;
                    $this->stack      = $addedProperties;

                    return;

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply POST method on');
            }
        }
    }

    /**
     * @param  string|null  $processor
     * @param  mixed        $requestBody
     *
     * @return  void
     *
     * @throws  InvalidInputException
     */
    private function applyPutProcessor(?string $processor, $requestBody): void
    {
        if (Variable::isIntval($processor) && $processor >= 1) {
            switch ($this->stackType) {
                case self::STACK_TYPE_CLASS:
                    $this->applyGetInstanceOnClass($processor);

                    $this->applySetPropertiesOnInstance($requestBody);

                    return;

                default:
                    throw new InvalidInputException($this->stackType, 'type to apply PUT instance on');
            }
        }
        else {
            $method = sprintf('set%s', ucfirst($processor));

            if ($this->stackType === self::STACK_TYPE_CLASS && method_exists($this->class, $method)) {
                $class = $this->class;

                $class::$method($requestBody);

                return;
            }

            if ($this->stackType === self::STACK_TYPE_CLASS) {
                $this->applyGetInstancesOnClass();
            }

            $this->applyPutProcessorOnInstanceProperty($processor, $requestBody);
        }
    }

    /**
     * @param  string  $property
     * @param  array   $requestBody
     *
     * @return  void
     *
     * @throws  InvalidInputException
     */
    private function applyPutProcessorOnInstanceProperty(string $property, array $requestBody): void
    {
        if (!$instance = Variable::keyval($this->stack, 0)) {
            return;
        }

        $method = sprintf('set%sSpecifications', ucfirst($property));

        if (!is_callable([$instance, $method])) {
            throw new InvalidInputException($method, sprintf('method of instance %s', $instance));
        }

        $instance->$method($requestBody);

        $this->returnType = self::RETURN_ONE_ENTITY;
    }

    /**
     * @param  mixed  $requestBody
     *
     * @return  void
     */
    private function applySetPropertiesOnInstance($requestBody): void
    {
        if (!$instance = Variable::keyval($this->stack, 0)) {
            return;
        }

        $setter = new RecursivePropertySetter($this->request);

        $setter->setPropertyValues($instance, $requestBody);
    }

    /**
     * @return  void
     */
    private function deleteInstance(): void
    {
        if (!$instance = Variable::keyval($this->stack, 0)) {
            return;
        }

        $instance->delete();
    }

    /**
     * @return  mixed
     *
     * @throws  InvalidInputException
     */
    private function returnOneEntity()
    {
        if (!is_array($this->stack)) {
            throw new InvalidInputException(
                ['stack' => $this->stack, 'returnType' => $this->returnType],
                'stack and return type combination'
            );
        }

        return array_shift($this->stack);
    }
}

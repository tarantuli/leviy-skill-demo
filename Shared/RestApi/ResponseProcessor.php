<?php
namespace Shared\RestApi;

use Shared\DataControl\Variable;
use Shared\Databases\Interfaces\DatasetInterface;
use Shared\Entities\Collection;
use Shared\Entities\Interfaces\EntityInterface;
use Shared\Exceptions\InvalidInputException;
use Shared\FileControl\Directory;
use Shared\FileControl\File;
use Shared\Providers\ProviderRegistry;

class ResponseProcessor
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    private $collectionMaxRows;

    /**
     * @var  int
     */
    private $collectionOffset;

    /**
     * @var  array
     */
    private $pathToClassMapping;

    /**
     * @var  string
     */
    private $publicFileDirectory;

    /**
     * @var  RequestData
     */
    private $requestData;

    /**
     * @var  mixed
     */
    private $response;

    /**
     * @var  Response
     */
    private $responseProcessor;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * ResponseProcessor constructor
     */
    public function __construct()
    {
        $this->responseProcessor = Response::get();
        $this->requestData = RequestData::get();
    }

    /**
     * @param  int  $maxRows
     */
    public function setCollectionMaxRows(int $maxRows): void
    {
        $this->collectionMaxRows = $maxRows;
    }

    /**
     * @param  int  $offset
     */
    public function setCollectionOffset(int $offset): void
    {
        $this->collectionOffset = $offset;
    }

    /**
     * @param  mixed  $response
     * @param  array  $pathToClassMapping
     *
     * @return  mixed
     */
    public function go($response, array $pathToClassMapping)
    {
        $this->response = $response;
        $this->pathToClassMapping = $pathToClassMapping;

        // Guarantee that $this->response is an array for now
        if (Variable::isNumericArray($this->response)) {
            $isCollection = true;
        }
        else {
            $this->response = [$this->response];
            $isCollection   = false;
        }

        // Turn each resource into a property array. Not recursively!
        // We want the expand properties to determine what to... expand
        // Remaining objects will be turned into resource URLs lateron
        foreach ($this->response as & $resource) {
            $resource = $this->getObjectPropertyValues($resource);
        }

        // Expand properties
        if ($expansions = $this->requestData->getExpands()) {
            $this->applyResponseExpansions($expansions);
        }

        // Apply response field filters if they were set
        if ($fields = $this->requestData->getFields()) {
            $this->applyResponseFieldFilters($fields);
        }

        // Add pagination headers
        if ($isCollection) {
            $this->responseProcessor->setHeaderValue('X-Collection-Count', (int) $this->collectionMaxRows);

            $offset = $this->collectionOffset === null
                ? $this->requestData->getOffset()
                : $this->collectionOffset;

            $this->responseProcessor->setHeaderValue('X-Collection-Offset', (int) $offset);
            $this->responseProcessor->setHeaderValue(
                'X-Collection-Limit',
                (int) $this->requestData->getLimit()
            );
        }

        // Reduce the array to the first item if it's not a collection
        if (!$isCollection && array_key_exists(0, $this->response)) {
            $this->response = $this->response[0];
        }

        /*
        		         * If the response is a single value true and trueAsNull is set, return null
        		         */
        if ($this->response === true && $this->requestData->hasParameter('returnTrueAsNull')) {
            $this->response = null;
        }

        // Turn remaining objects into resource URLs
        if (is_array($this->response)) {
            array_walk_recursive($this->response, [$this, 'objectToBasicArray']);
        }
        elseif (is_object($this->response)) {
            $this->response = $this->response->getRestProperties();
        }

        return $this->response;
    }

    /**
     * @param  string  $directory
     *
     * @throws  InvalidInputException
     */
    public function setPublicFileDirectory(string $directory): void
    {
        if (!Directory::ensureExistence($directory)) {
            throw new InvalidInputException($directory, 'directory');
        }

        $this->publicFileDirectory = $directory;
    }

    /**
     * Parses the given expansions and applies them to the selected resources
     *
     * @param  array  $expansions
     *
     * @return  void
     */
    private function applyResponseExpansions(array $expansions): void
    {
        $lookup = RestFunctions::arrayToLookup($expansions);

        foreach ($lookup as $keyPath => $node) {
            $refsToExpand = [];

            foreach ($this->response as & $resource) {
                $keys = explode(SLASH, $keyPath);
                $ref  =& $resource;

                if (is_object($ref)) {
                    $ref = $ref::getRestProperties();
                }

                if (is_scalar($ref)) {
                    continue;
                }

                foreach ($keys as $key) {
                    if (is_array($ref) && !array_key_exists($key, $ref)) {
                        continue;
                    }

                    $ref =& $ref[$key];
                }

                $refsToExpand[] =& $ref;
            }

            // Fetch data collectively
            Collection::fetchData($refsToExpand);

            foreach ($refsToExpand as & $ref) {
                $this->expandRef($ref);
            }
        }
    }

    /**
     * Parses the given response field filters and applies them to the selected
     * resources
     *
     * @param  array  $fields
     *
     * @return  void
     */
    private function applyResponseFieldFilters(array $fields): void
    {
        $lookup = RestFunctions::arrayToLookup($fields);

        foreach ($this->response as & $resource) {
            $stack = [];

            foreach ($resource as $key => & $node) {
                $stack[] = [
                    'key_path'   => [$key],
                    'node'       => & $node,
                    'parent'     => & $resource,
                    'parent_key' => $key
                ];
            }

            while ($stack) {
                $frame    = array_pop($stack);
                $path     = implode(SLASH, $frame['key_path']);
                $barePath = preg_replace('#/\d+/#', SLASH, $path);

                if (!is_numeric($frame['parent_key']) && !Variable::hasKey($lookup, $barePath)) {
                    unset($frame['parent'][$frame['parent_key']]);

                    continue;
                }

                if (is_array($frame['node'])) {
                    foreach ($frame['node'] as $key => & $node) {
                        $keyPath = array_merge($frame['key_path'], [$key]);

                        $stack[] = [
                            'key_path'   => $keyPath,
                            'node'       => & $node,
                            'parent'     => & $frame['node'],
                            'parent_key' => $key,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Returns the resource name for the given class name
     *
     * @param  string  $className
     *
     * @return  string
     */
    private function classToEndpoint(string $className): ?string
    {
        $endpoint = array_search(BACKSLASH . $className, $this->pathToClassMapping);

        if ($endpoint === false and $provider = ProviderRegistry::get()->forEntityClass($className)) {
            $endpoint = array_search(BACKSLASH . get_class($provider), $this->pathToClassMapping);

            if ($endpoint === false) {
                return null;
            }
        }

        return str_replace(BACKSLASH, SLASH, $endpoint);
    }

    private function expandRef(& $ref): void
    {
        if (is_array($ref)) {
            foreach ($ref as & $item) {
                $item = $this->getObjectPropertyValues($item);
            }
        }
        elseif (is_object($ref)) {
            $ref = $this->getObjectPropertyValues($ref);
        }

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        elseif (is_bool($ref) || $ref === null) {
            // Do nothing
        }
        else {
            throw new InvalidInputException($ref, 'reference to expand');
        }
    }

    /**
     * @param  File  $file
     *
     * @return  string
     */
    private function turnFileIntoPublicPath(File $file): string
    {
        $path = sprintf('%s/%s', $this->publicFileDirectory, $file->getHash());

        File::ensureExistence($path, $file);

        return $path;
    }

    /**
     * Returns the restful properties of the given object
     *
     * @param  mixed  $object
     *
     * @return  mixed
     */
    private function getObjectPropertyValues($object)
    {
        RestFunctions::doMemoryCheck();

        if (!is_object($object)) {
            return $object;
        }

        if ($object instanceof DatasetInterface) {
            return $object->toArray();
        }

        if (!$object instanceof Interfaces\ResourceInterface) {
            if ($object instanceof Interfaces\CastableToArrayInterface) {
                return $object->toRestResponseArray();
            }

            return [];
        }

        $properties     = $object->getRestProperties();
        $propertyValues = [];

        if ($object instanceof EntityInterface) {
            $propertyValues['id']    = $object->id();
            $propertyValues['state'] = $object->getState();
        }

        foreach ($properties as $name => $property) {
            if (!$getter = Variable::keyval($property, 'getter')) {
                continue;
            }

            $propertyValues[$name] = $object->$getter();
        }

        if ($object instanceof Interfaces\AfterGettingRestPropertiesInterface) {
            $object->runAfterGettingRestProperties();
        }

        RestFunctions::doMemoryCheck();

        $propertyValues['href'] = $this->objectToUrl($object);

        return $propertyValues;
    }

    /**
     * @param  mixed  &$resource
     *
     * @return  void
     *
     * @throws  Exceptions\CannotConvertToRestResponseException
     */
    private function objectToBasicArray(& $resource): void
    {
        if (!is_object($resource)) {
            return;
        }

        if ($resource instanceof File && $this->publicFileDirectory) {
            $resource = ['path' => $this->turnFileIntoPublicPath($resource)];
        }
        elseif ($resource instanceof Interfaces\CastableToArrayInterface) {
            $href     = $this->objectToUrl($resource);
            $resource = $resource->toRestResponseArray();

            if ($href) {
                $resource['href'] = $href;
            }
        }
        elseif ($resource instanceof Interfaces\CastableToStringInterface) {
            $resource = $resource->toRestResponseString();
        }
        elseif ($resource instanceof Interfaces\CastableToIntInterface) {
            $resource = $resource->toRestResponseInt();
        }
        else {
            throw new Exceptions\CannotConvertToRestResponseException(get_class($resource));
        }
    }

    /**
     * If the given resource is an instance, it is turned into a REST resource URL
     *
     * @param  mixed  $resource
     *
     * @return  string
     */
    private function objectToUrl($resource): ?string
    {
        if (!is_object($resource)) {
            return $resource;
        }

        if (null === $endpoint = $this->resourceToEndpoint($resource)) {
            return null;
        }

        // Replace the resource by a URL string or a placeholder text for non-REST classes
        $id = $resource instanceof EntityInterface ? $resource->id() : 0;

        return sprintf(RequestProcessor::get()->getObjectToUrlTemplate(), $endpoint, $id);
    }

    /**
     * @param  mixed  $resource
     *
     * @return  string
     */
    private function resourceToEndpoint($resource): ?string
    {
        if ($endpoint = $this->classToEndpoint(get_class($resource))) {
            return $endpoint;
        }

        foreach (class_parents($resource) as $parent) {
            $endpoint = $this->classToEndpoint($parent);

            if ($endpoint !== false) {
                return $endpoint;
            }
        }

        return null;
    }
}

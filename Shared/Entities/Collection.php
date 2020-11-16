<?php
namespace Shared\Entities;

use Closure;
use Shared\Databases\Filter;
use Shared\Exceptions\CaseNotImplementedException;

class Collection
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  array   $objects
     * @param  string  $method
     *
     * @return  array
     */
    public static function apply(array $objects, string $method): array
    {
        $results = [];

        foreach ($objects as $object) {
            $results[] = $object->$method();
        }

        return $results;
    }

    public static function fetchData(array $objects)
    {
        (new CollectionDataFetcher($objects))->go();
    }

    /**
     * @param  array  $objects
     * @param  array  $filters
     *
     * @return  array
     */
    public static function filter(array $objects, array $filters): array
    {
        $filteredObjects = [];

        foreach ($objects as $object) {
            $passesFilters = true;

            foreach ($filters as $filter) {
                if (!self::passesFilter($object, $filter)) {
                    $passesFilters = false;

                    break;
                }
            }

            if ($passesFilters) {
                $filteredObjects[] = $object;
            }
        }

        return $filteredObjects;
    }

    /**
     * Finds the next entity after the one with the ID of the current instance;
     * otherwise, it returns the first instance of the collection, allowing seamless
     * looping and initialization
     *
     * @param  Interfaces\EntityInterface    $currentInstance
     * @param  Interfaces\EntityInterface[]  $instances
     *
     * @return  mixed|null
     */
    public static function findNextInstance(Interfaces\EntityInterface $currentInstance, array $instances)
    {
        $firstInstance     = null;
        $nextIsNexInstance = false;

        foreach ($instances as $anInstance) {
            if ($nextIsNexInstance === true) {
                return $anInstance;
            }

            if ($firstInstance === null) {
                $firstInstance = $anInstance;
            }

            if ($currentInstance->id() === $anInstance->id()) {
                $nextIsNexInstance = true;
            }
        }

        return $firstInstance;
    }

    /**
     * @param  Interfaces\EntityInterface[]  $objects
     *
     * @return  array
     */
    public static function getIds(array $objects): array
    {
        $ids = [];

        foreach ($objects as $object) {
            $ids[] = $object->id();
        }

        return $ids;
    }

    /**
     * @param  array  &$objects
     * @param  array  $methods
     *
     * @return  void
     */
    public static function multisort(& $objects, array $methods): void
    {
        usort($objects, self::multisortMethod($methods));
    }

    public static function multisortStringToMethodArray(string $string): array
    {
        $methods  = [];
        $sortings = explode(',', $string);

        foreach ($sortings as $sorting) {
            $direction = substr($sorting, 0, 1);
            $method    = 'get' . ucfirst(substr($sorting, 1));

            if ($direction === '>') {
                $methods[$method] = true;
            }
            else {
                $methods[$method] = false;
            }
        }

        return $methods;
    }

    /**
     * @param  array   &$objects
     * @param  string  $method
     * @param  bool    $reverse
     *
     * @return  void
     */
    public static function sort(& $objects, string $method, bool $reverse = false): void
    {
        usort($objects, self::sortMethod($method, $reverse));
    }

    /**
     * @param  array  $methods
     *
     * @return  Closure
     */
    private static function multisortMethod(array $methods): Closure
    {
        return function ($a, $b) use ($methods)
        {
            foreach ($methods as $method => $flipDirection) {
                $va  = $a->$method();
                $vb  = $b->$method();
                $cmp = (is_int($va) || is_float($va)) ? $va - $vb : strcasecmp($va, $vb);

                if ($flipDirection) {
                    $cmp = -$cmp;
                }

                $cmp = (int) floor($cmp * 100);

                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return 0;
        };
    }

    /**
     * @param  mixed  $object
     * @param  array  $filter
     *
     * @return  bool
     *
     * @throws  CaseNotImplementedException
     */
    private static function passesFilter(object $object, array $filter): bool
    {
        switch ($filter[0]) {
            case Filter::IS_EQUAL:
                $method = $filter[1];

                return $object->$method() === $filter[2];

            default:
                throw new CaseNotImplementedException($filter[0]);
        }
    }

    /**
     * @param  string  $method
     * @param  bool    $reverse
     *
     * @return  Closure
     */
    private static function sortMethod(string $method, bool $reverse): Closure
    {
        return function ($a, $b) use ($method, $reverse)
        {
            $resultA = $a->$method();
            $resultB = $b->$method();

            if ($resultA === null && $resultB === null) {
                $result = 0;
            }
            elseif ($resultA === null) {
                $result = -1;
            }
            elseif ($resultB === null) {
                $result = +1;
            }
            elseif (is_string($resultA)) {
                $result = strcasecmp($resultA, $resultB);
            }
            else {
                $result = $resultA - $resultB;
            }

            return $reverse ? -$result : $result;
        };
    }
}

<?php
namespace Shared\RestApi;

use Shared\Databases\Filter;
use Shared\Exceptions\CaseNotImplementedException;
use Shared\Exceptions\InvalidInputException;
use Shared\System\Exceptions\AlmostOutOfMemoryException;
use Shared\System\Info;

/**
 * RESTful helper functions
 */
class RestFunctions
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Creates a 1-dimensional lookup array for the given multidimensional array
     *
     * @param  array  $array
     *
     * @return  array
     */
    public static function arrayToLookup(array $array)
    {
        $stack = [];

        foreach ($array as $key => & $node) {
            $stack[] = ['keyPath' => [$key], 'node' => & $node];
        }

        $lookup = [];

        while ($stack) {
            $frame = array_pop($stack);
            $lookup[implode('/', $frame['keyPath'])] =& $frame['node'];

            if (is_array($frame['node'])) {
                foreach ($frame['node'] as $key => & $node) {
                    $keyPath = array_merge($frame['keyPath'], [$key]);
                    $stack[] = ['keyPath' => $keyPath, 'node' => & $node];
                    $lookup[implode('/', $keyPath)] =& $node;
                }
            }
        }

        return $lookup;
    }

    /**
     * Executes the given method on the given instance, parsing the associated array
     * of arguments for the method's named parameters
     *
     * @param  mixed   $instance
     * @param  string  $method
     * @param  array   $arguments
     *
     * @return  mixed
     */
    public static function executeInstanceMethod($instance, string $method, array $arguments)
    {
        return $instance->callWithAssociatedArray($method, $arguments);
    }

    /**
     * Checks whether the current memory usage is close to the limit. If so, it stops
     * processing and sends an error response
     *
     * @throws  AlmostOutOfMemoryException
     */
    public static function doMemoryCheck()
    {
        static $criticalValue;

        if ($criticalValue === null) {
            $criticalValue = .9 * Info::getMemoryLimit();
        }

        if (memory_get_usage() <= $criticalValue) {
            return null;
        }

        throw new AlmostOutOfMemoryException();
    }

    /**
     * @param  string  $name
     *
     * @return  array
     */
    public static function normalizeFilterOperator(string $name): array
    {
        $operator = '=';

        // Remove (#) from the end of the name, they are markers to allow multiple query parameters
        // of the same name in PHP
        $name     = preg_replace('/\(\d+\)$/', '', $name);
        $lastChar = substr($name, -1);

        if (false !== strpos('-~^$*><@!', $lastChar)) {
            if ($lastChar !== '-') {
                $operator = $lastChar . $operator;
            }

            $name = substr($name, 0, -1);
        }

        return [$operator, $name];
    }

    /**
     * Based on http://stackoverflow.com/a/17508226 fetched on 2015-07-02
     *
     * @param  string  $string
     *
     * @return  array
     *
     * @throws  InvalidInputException
     */
    public static function parseTerseObjectString(string $string)
    {
        // we will always have a "current context".  the current context is the array we're
        // currently operating in.  when we start, this is simply an empty array.  as new
        // arrays are created, this context will change
        $context = [];

        // since we have to keep track of how deep our context is, we keep a context stack
        $contextStack = [& $context];

        // this accumulates the name of the current array
        $name = '';

        for ($i = 0; $i < strlen($string); $i++) {
            switch ($string[$i]) {
                case ',':
                    // if the last array hasn't been added to the current context
                    // (as will be the case for arrays lacking parens), we add it now
                    if ($name != '' && !array_key_exists($name, $context)) {
                        $context[$name] = [];
                    }

                    // reset name accumulator
                    $name = '';

                    break;

                case '(':
                    // we are entering a subcontext
                    // save a new array in the current context; this will become our new context
                    $context[$name] = [];

                    // switch context and add to context stack
                    $context =& $context[$name];
                    $contextStack[] =& $context;

                    // reset name accumulator
                    $name = '';

                    break;

                case ')':
                    // we are exiting a context
                    // if we haven't saved this array in the current context, do so now
                    if ($name != '' && !array_key_exists($name, $context)) {
                        $context[$name] = [];
                    }

                    // we can't just assign $context the return value of array_pop because
                    // that does not return a reference
                    array_pop($contextStack);

                    if (count($contextStack) == 0) {
                        throw new InvalidInputException($string, 'terse object string');
                    }

                    $context =& $contextStack[count($contextStack) - 1];

                    // reset name accumulator
                    $name = '';

                    break;

                default:
                    // this is part of the field name
                    $name .= $string[$i];
            }
        }

        // add any trailing arrays to the context (this will cover the case
        // where our input ends in an array without parents)
        if ($name != '' && !array_key_exists($name, $context)) {
            $context[$name] = [];
        }

        if (count($contextStack) != 1) {
            throw new InvalidInputException($string, 'terse object string');
        }

        return array_pop($contextStack);
    }

    /**
     * Turns the filter array from Response::getDatastoreFilters() into a Datastore
     * filter
     *
     * @param  array  $restFilter
     *
     * @return  array
     *
     * @throws  CaseNotImplementedException
     */
    public static function restFilterToDatastoreFilter(array $restFilter): array
    {
        [$field, $operator, $value] = $restFilter;

        if ($operator === '=') {
            [$operator, $field] = self::normalizeFilterOperator($field);
        }

        $values = is_array($value) ? $value : explode(',', $value);

        switch ($operator) {
            case '=':
                if ($field === 'sort') {
                    return Filter::sortAsc(array_pop($values));
                }

                if ($field === 'sortDesc') {
                    return Filter::sortDesc(array_pop($values));
                }

                if ($field === 'multisort') {
                    return Filter::multisort(array_pop($values));
                }

                if ($values === ['null']) {
                    return Filter::isNull($field);
                }

                if ($values === ['']) {
                    return Filter::isEqual($field, '');
                }

                return Filter::isEqual($field, $values);

            case '!=':
                return Filter::isNotEqual($field, $values);

            case '*=':
                return Filter::contains($field, $values);

            case '^=':
                return Filter::startsWith($field, $values);

            case '$=':
                return Filter::endsWith($field, $values);

            case '>=':
                return Filter::isAtLeast($field, $values);

            case '<=':
                return Filter::isAtMost($field, $values);
        }

        throw new CaseNotImplementedException($operator);
    }
}

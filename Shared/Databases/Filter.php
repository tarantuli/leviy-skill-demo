<?php
namespace Shared\Databases;

/**
 * (summary missing)
 */
class Filter
{
    /*****************
     *   Constants   *
     ****************/

    public const CHANGED_FOR_ACCOUNT = 26;
    public const ENDS_WITH        = 23;
    public const FULL_TEXT_SEARCH = 13;
    public const FULL_TEXT_SEARCH_IN_BOOLEAN_MODE = 14;
    public const GROUP_BY         = 17;
    public const INCREMENT_COLUMN = 24;
    public const IS_AT_LEAST      = 8;
    public const IS_AT_MOST       = 7;
    public const IS_BETWEEN       = 21;
    public const IS_EQUAL         = 1;
    public const IS_LESS_THAN     = 9;
    public const IS_LESS_THAN_OR_EQUAL = 7;
    public const IS_LIKE          = 2;
    public const IS_MORE_THAN     = 10;
    public const IS_MORE_THAN_OR_EQUAL = 8;
    public const IS_NOT_EQUAL     = 12;
    public const IS_NOT_NULL      = 19;
    public const IS_NOT_RLIKE     = 28;
    public const IS_NULL          = 11;
    public const IS_RLIKE         = 27;
    public const MAX_ROW_COUNT    = 5;
    public const MULTISORT        = 25;
    public const ORDER_ASC        = 3;
    public const ORDER_DESC       = 4;
    public const ROW_OFFSET       = 6;
    public const SHUFFLE          = 16;
    public const STARTS_WITH      = 22;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a filter on records where the first argument is more than or equal to
     * the second
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isAtLeast($a, $b): array
    {
        return [self::IS_AT_LEAST, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument is less than or equal to
     * the second
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isAtMost($a, $b): array
    {
        return [self::IS_AT_MOST, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument is between the second and
     * the third
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     * @param  mixed  $c  The third argument
     *
     * @return  array
     */
    public static function isBetween($a, $b, $c): array
    {
        return [self::IS_BETWEEN, $a, [$b, $c]];
    }

    public static function isChangedForAccount($accountId)
    {
        return [self::CHANGED_FOR_ACCOUNT, $accountId];
    }

    /**
     * Returns a filter on records where the first argument contains the second
     * argument
     *
     * @param  mixed  $a  The argument
     * @param  mixed  $b  The argument
     *
     * @return  array
     */
    public static function contains($a, $b): array
    {
        return [self::IS_LIKE, $a, $b];
    }

    /**
     * Returns a piece of SQL code to calculate the distance between points defined
     * by `latitude` and `longitude` to the given location defined by $latitude and
     * $longitude
     *
     * @param  float  $latitude   The latitude of the location
     * @param  float  $longitude  The longitude of the location
     *
     * @return  string
     */
    public static function distanceTo(float $latitude, float $longitude): string
    {
        // Distance to (@latitude, @longitude) in meters is given by:
        //
        // 1000 * 6371 * ACOS(
        // SIN(RADIANS(`latitude`)) * SIN(RADIANS(@latitude))
        // + COS(RADIANS(`latitude`)) * COS(RADIANS(@latitude)) * COS(RADIANS(@longitude) - RADIANS(`longitude`))
        // )
        return sprintf(
            '6371000 * ACOS(SIN(RADIANS(`latitude`))*SIN(RADIANS(%f))+COS(RADIANS(`latitude`))*COS(RADIANS(%f))*COS(RADIANS(%f)-RADIANS(`longitude`)))',
            $latitude,
            $latitude,
            $longitude
        );
    }

    /**
     * Returns a filter on records where the first argument doesn't regexp match the
     * second argument
     *
     * @param  mixed  $a  The argument
     * @param  mixed  $b  The argument
     *
     * @return  array
     */
    public static function doesNotMatch($a, $b): array
    {
        return [self::IS_NOT_RLIKE, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument ends with second argument
     *
     * @param  mixed  $a  The argument
     * @param  mixed  $b  The argument
     *
     * @return  array
     */
    public static function endsWith($a, $b): array
    {
        return [self::ENDS_WITH, $a, $b];
    }

    /**
     * Returns a filter on records where both arguments are equal
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isEqual($a, $b): array
    {
        return [self::IS_EQUAL, $a, $b];
    }

    /**
     * Returns a filter that tells the database to group the rows by the given
     * argument
     *
     * @param  mixed  $column  The result column to group by
     *
     * @return  array
     */
    public static function groupBy($column): array
    {
        return [self::GROUP_BY, $column];
    }

    /**
     * Returns a filter that indicates that a column should be incremented
     *
     * @param  string  $column
     * @param  int     $amount
     *
     * @return  array
     */
    public static function incrementColumn(string $column, int $amount = 1): array
    {
        return [self::INCREMENT_COLUMN, $column, $amount];
    }

    /**
     * Returns a filter on records where the first argument is less than the second
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isLessThan($a, $b): array
    {
        return [self::IS_LESS_THAN, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument is less than or equal to
     * the second
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isLessThanOrEqual($a, $b): array
    {
        return [self::IS_LESS_THAN_OR_EQUAL, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument contains the second
     * argument
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isLike($a, $b): array
    {
        return [self::IS_LIKE, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument regexp matches the second
     * argument
     *
     * @param  mixed  $a  The argument
     * @param  mixed  $b  The argument
     *
     * @return  array
     */
    public static function matches($a, $b): array
    {
        return [self::IS_RLIKE, $a, $b];
    }

    /**
     * Returns a filter that tells the database to return at most the given amount of
     * records
     *
     * @param  int  $maxAmount  The maximum amount
     *
     * @return  array
     */
    public static function maxRowCount(int $maxAmount): array
    {
        return [self::MAX_ROW_COUNT, $maxAmount];
    }

    /**
     * Returns a filter on records where the first argument is more than the second
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isMoreThan($a, $b): array
    {
        return [self::IS_MORE_THAN, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument is more than or equal to
     * the second
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isMoreThanOrEqual($a, $b): array
    {
        return [self::IS_MORE_THAN_OR_EQUAL, $a, $b];
    }

    public static function multisort($columns)
    {
        return [self::MULTISORT, $columns];
    }

    /**
     * Returns a filter on records that are within a given distance of a given
     * location
     *
     * @param  float  $latitude     The latitude of the location
     * @param  float  $longitude    The longitude of the location
     * @param  float  $maxDistance  The maximum allowed distance to the location in meters
     *
     * @return  array
     */
    public static function nearLocation(float $latitude, float $longitude, float $maxDistance): array
    {
        return [self::IS_AT_MOST, self::distanceTo($latitude, $longitude), $maxDistance];
    }

    /**
     * Returns a filter on records where both arguments are not equal
     *
     * @param  mixed  $a  The first argument
     * @param  mixed  $b  The second argument
     *
     * @return  array
     */
    public static function isNotEqual($a, $b): array
    {
        return [self::IS_NOT_EQUAL, $a, $b];
    }

    /**
     * Returns a filter on records where the argument is not null
     *
     * @param  mixed  $a  The argument
     *
     * @return  array
     */
    public static function isNotNull($a): array
    {
        return [self::IS_NOT_NULL, $a];
    }

    /**
     * Returns a filter on records where the argument is null
     *
     * @param  mixed  $a  The argument
     *
     * @return  array
     */
    public static function isNull($a): array
    {
        return [self::IS_NULL, $a];
    }

    /**
     * Returns a filter that tells the database to start returning rows from the
     * given number
     *
     * @param  int|null  $offset
     *
     * @return  array
     */
    public static function rowOffset(?int $offset): array
    {
        return [self::ROW_OFFSET, $offset];
    }

    /**
     * Returns a filter that tells the database to shuffle the rows, randomizing
     * their order
     *
     * @return  array
     */
    public static function shuffle(): array
    {
        return [self::SHUFFLE];
    }

    /**
     * @param  mixed  $columns
     *
     * @return  array
     */
    public static function sort($columns): array
    {
        return [self::ORDER_ASC, explode(',', $columns)];
    }

    /**
     * Returns a filter that tells the database to sort the rows by the given
     * argument, lowest values first
     *
     * @param  mixed  $column  The result column to sort on
     *
     * @return  array
     */
    public static function sortAsc($column): array
    {
        return [self::ORDER_ASC, $column];
    }

    /**
     * Returns a filter that tells the database to sort the rows by the distance to
     * the given point
     *
     * @param  float  $latitude   The latitude of the location
     * @param  float  $longitude  The longitude of the location
     *
     * @return  array
     */
    public static function sortByDistanceTo(float $latitude, float $longitude): array
    {
        return self::sortAsc(self::distanceTo($latitude, $longitude));
    }

    /**
     * Returns a filter that tells the database to sort the rows by the given
     * argument, highest values first
     *
     * @param  mixed  $column  The result column to sort on
     *
     * @return  array
     */
    public static function sortDesc($column): array
    {
        return [self::ORDER_DESC, $column];
    }

    /**
     * Returns a filter on records where the first argument starts with second
     * argument
     *
     * @param  mixed  $a  The argument
     * @param  mixed  $b  The argument
     *
     * @return  array
     */
    public static function startsWith($a, $b): array
    {
        return [self::STARTS_WITH, $a, $b];
    }

    /**
     * Returns a filter on records where the first argument is matches against the
     * second using full-text searches
     *
     * @param  mixed  $a              The first argument
     * @param  mixed  $b              The second argument
     * @param  bool   $inBooleanMode  Whether to search in boolean mode or not
     *
     * @return  array
     */
    public static function textMatches($a, $b, bool $inBooleanMode = false): array
    {
        $searchType = $inBooleanMode ? self::FULL_TEXT_SEARCH_IN_BOOLEAN_MODE : self::FULL_TEXT_SEARCH;

        return [$searchType, $a, $b];
    }
}

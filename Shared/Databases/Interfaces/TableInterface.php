<?php
namespace Shared\Databases\Interfaces;

/**
 * (summary missing)
 */
interface TableInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  string  $name
     *
     * @return  ColumnInterface|null
     */
    public function getColumn(string $name): ?ColumnInterface;

    /**
     * Returns all constraint names on the given field
     *
     * @param  string  $field
     *
     * @return  string[]
     */
    public function getConstraintsOnField(string $field): array;

    /**
     * @return  array[]
     */
    public function getConstraintsPerField();

    /**
     * @param  array[]  $conditions
     *
     * @return  int
     */
    public function getCount(array $conditions = null): int;

    /**
     * @return  ServerInterface
     */
    public function getDatabase();

    /**
     * Returns the number of found rows by the last limited query
     *
     * @return  int
     */
    public function getFoundRows();

    /**
     * @param  array  $fields
     * @param  array  $conditions
     *
     * @return  array
     */
    public function getList(array $fields, array $conditions): array;

    /**
     * @param  array  $modifications
     * @param  array  $conditions
     * @param  array  $creationValues
     *
     * @return  bool
     */
    public function modifyOrCreateRecord(array $modifications, array $conditions, array $creationValues = []): bool;

    /**
     * @param  array       $modifications
     * @param  array       $conditions
     * @param  array|null  $orderBy
     * @param  int|null    $limit
     * @param  bool        $ignoreErrors
     *
     * @return  bool|int
     */
    public function modifyRecord(array $modifications, array $conditions, ?array $orderBy = null, ?int $limit = null, bool $ignoreErrors = false): int;

    /**
     * Returns the name
     *
     * @return  string
     */
    public function getName();

    /**
     * @param  array  $values
     *
     * @return  int
     */
    public function createOrReplaceRecord(array $values): int;

    /**
     * @param  array  $values
     * @param  bool   $ignoreErrors
     *
     * @return  int
     */
    public function createRecord(array $values, bool $ignoreErrors = false);

    /**
     * @param  array  $conditions
     * @param  bool   $ignoreErrors
     *
     * @return  int
     */
    public function deleteRecord(array $conditions, bool $ignoreErrors = false): int;

    /**
     * @param  array|null  $fields
     * @param  array       $conditions
     *
     * @return  RecordInterface|null
     */
    public function getRecord(?array $fields, array $conditions): ?RecordInterface;

    /**
     * @param  array  $values
     * @param  bool   $ignoreErrors
     *
     * @return  int
     */
    public function createRecordAndReturnAffectedCount(array $values, bool $ignoreErrors = false): int;

    /**
     * @param  array|null  $fields
     * @param  array|null  $conditions
     * @param  array|null  $orderBy
     * @param  mixed       $limit
     * @param  array|null  $groupBy
     * @param  array|null  $having
     * @param  array       $connectionTables
     *
     * @return  DatasetInterface
     */
    public function getRecords(array $fields = null, array $conditions = null, array $orderBy = null, $limit = null, array $groupBy = null, array $having = null, array $connectionTables = []);

    /**
     * Returns values by parsing the given filters for where clauses, ordering
     * clauses and limiting clauses
     *
     * @param  array|null  $fields            The fields to return
     * @param  array       $filters           An array of filters
     * @param  array       $connectionTables
     *
     * @return  DatasetInterface  An array of values that match the conditions
     */
    public function getRecordsFromFilters(?array $fields, array $filters, array $connectionTables = []): DatasetInterface;

    /**
     * @param  array  $values
     *
     * @return  int
     */
    public function replaceRecord(array $values): int;

    /**
     * @return  string
     */
    public function getStructure();

    /**
     * @return  array
     */
    public function getUniqueFieldSets();

    /**
     * @param  string  $field
     * @param  array   $conditions
     *
     * @return  string
     */
    public function getValue(string $field, array $conditions = []): ?string;

    /**
     * @param  string      $field
     * @param  array|null  $conditions
     * @param  array|null  $orderBy
     * @param  mixed       $limit
     *
     * @return  string[]
     */
    public function getValues(string $field, ?array $conditions = [], ?array $orderBy = null, $limit = null): array;

    /**
     * GetValues using filters (see getRecordsFromFilters)
     *
     * @param  string      $field             The field to return
     * @param  array|null  $filters           An array of filters
     * @param  array       $connectionTables
     *
     * @return  array
     */
    public function getValuesFromFilters(string $field, ?array $filters = [], array $connectionTables = []): array;
}

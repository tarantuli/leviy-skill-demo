<?php
namespace Shared\Entities\Interfaces;

use Shared\DataControl\Stores\Interfaces\StoreInterface;
use Shared\Databases\Interfaces\TableInterface;
use Shared\Types\Interfaces\TypeInterface;

interface EntityProviderInterface
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * @return  EntityProviderInterface
     */
    public static function get(): self;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  int
     */
    public function getActiveInstanceCount(): int;

    /**
     * Finds active instances matching the given filters. See getIDs() for more
     * information
     *
     * @param  array  $filters  An array of Filter arrays
     *
     * @return  array
     */
    public function getActiveInstances(array $filters = []): array;

    /**
     * @param  array  $values
     */
    public function checkExistenceOfValues(array $values): void;

    /**
     * @param  string  $property
     * @param  mixed   &$value
     */
    public function checkInput(string $property, & $value): void;

    /**
     * @param  mixed[]  $data
     *
     * @return  void
     */
    public function checkUniquenessConstraints(array $data): void;

    /**
     * Returns the entity class that this provider controls
     *
     * @return  string
     */
    public function getEntityClass(): string;

    /**
     * Returns an array of instances of the called class with the given IDs
     *
     * @param  array  $ids  An array of IDs
     *
     * @return  array
     */
    public function fromIds(array $ids): array;

    /**
     * @return  TableInterface
     */
    public function getHistoryTable(): ?TableInterface;

    /**
     * Finds IDs of instances matching the given filters and conditions
     *
     * @param  array  $filters  An array of Filter arrays
     *
     * @return  int[]
     */
    public function getIds(array $filters = []): array;

    /**
     * Create an instance of this class with the given data. It assumes that the data
     * is normalized and validated
     *
     * @param  array  $data
     *
     * @return  mixed
     */
    public function createInstance(array $data = []);

    /**
     * @param  int         $id
     * @param  array|null  $data
     *
     * @return  EntityInterface
     */
    public function getInstance(int $id, array $data = null);

    /**
     * Returns one instance with the given filters
     *
     * @param  array  $filters
     *
     * @return  mixed
     */
    public function getInstanceFromFilters(array $filters);

    /**
     * Finds instances matching the given filters. See getIDs() for more information
     *
     * @param  array  $filters  An array of Filter arrays
     *
     * @return  array
     */
    public function getInstances(array $filters = []): array;

    /**
     * Checks if there's a record with the given values. If so, it returns that
     * instance, otherwise it creates one using the given values
     *
     * @param  array  $selectorData  An associated array of values
     * @param  array  $creationData  An associated array of values
     *
     * @return  mixed
     */
    public function getOrCreateInstance(array $selectorData, array $creationData = []);

    /**
     * @param  string  $property
     *
     * @return  TableInterface
     */
    public function getPropertyConnectionsTable(string $property): ?TableInterface;

    /**
     * @return  string[]
     */
    public function getPropertyConnectionTables(): array;

    public function getPropertyStore(string $property): ?StoreInterface;

    /**
     * @param  string  $property
     *
     * @return  TypeInterface
     */
    public function getPropertyTypeInstance(string $property): TypeInterface;

    /**
     * Returns a filter that selects those entities that the caller is allowed to
     * read
     *
     * @return  array
     */
    public function getReadFiltersForCaller(): array;

    /**
     * @return  array
     */
    public function getRestProperties(): array;

    /**
     * @return  string
     */
    public function getSingularName(): string;

    /**
     * @return  TableInterface
     */
    public function storage(): TableInterface;

    /**
     * @return  TableInterface[]
     */
    public function storages(): array;

    /**
     * @return  array[]
     */
    public function getUniquePropertySets(): array;
}

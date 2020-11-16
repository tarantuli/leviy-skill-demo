<?php
namespace Shared\DataControl\Stores\Interfaces;

use Shared\Databases\Interfaces\TableInterface;
use Shared\Exceptions\InvalidInputException;

interface StoreInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Turns the given string into an ID
     *
     * @param  string  $string
     *
     * @return  int|null
     */
    public function getId(string $string): ?int;

    /**
     * Returns the string stored under the given ID
     *
     * @param  int|null  $id
     *
     * @return  string|null
     *
     * @throws  InvalidInputException
     */
    public function getString(?int $id): ?string;

    /**
     * Returns the strings stored under the given IDs
     *
     * @param  int[]  $ids
     *
     * @return  string[]
     */
    public function getStringsfromIds(array $ids): array;

    /**
     * @return  TableInterface
     */
    public function getTable(): TableInterface;

    /**
     * @return  string
     */
    public function getTableName(): string;

    /**
     * Returns whether this store uses a hashing column
     *
     * @return  bool
     */
    public function usesHashColumn(): bool;
}

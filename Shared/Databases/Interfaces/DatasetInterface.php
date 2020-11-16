<?php
namespace Shared\Databases\Interfaces;

use Iterator;

/**
 * (summary missing)
 */
interface DatasetInterface extends Iterator
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Resets the record iterator, but doesn't fetch the first record
     */
    public function reset();

    /**
     * Returns the number of records
     *
     * @return  int
     */
    public function count();

    /**
     * Returns the current record
     *
     * @return  array
     */
    public function current();

    /**
     * Returns the values in the first column as an array
     *
     * @return  array
     */
    public function getFirstColumn();

    /**
     * Returns the current index
     *
     * @return  int
     */
    public function key();

    /**
     * @return  array
     */
    public function getList();

    /**
     * Fetches the next record
     *
     * @return  void
     */
    public function next();

    /**
     * Returns the next record
     *
     * @return  RecordInterface|null
     */
    public function getRecord();

    /**
     * Resets the record iterator
     */
    public function rewind();

    /**
     * Returns the next record as a numerical array
     *
     * @return  array
     */
    public function getRow();

    /**
     * @return  string[]
     */
    public function to1DAssoc();

    /**
     * @return  array[]
     */
    public function to2DAssoc();

    public function toArray(): array;

    /**
     * @return  string[]
     */
    public function toColAssoc();

    /**
     * Returns whether the iterator is at a valid point
     *
     * @return  bool
     */
    public function valid();

    /**
     * Returns the first value of the next record
     *
     * @return  string
     */
    public function getValue();
}

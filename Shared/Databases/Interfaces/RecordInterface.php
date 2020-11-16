<?php
namespace Shared\Databases\Interfaces;

use ArrayAccess;

/**
 * (summary missing)
 */
interface RecordInterface extends ArrayAccess
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  int
     */
    public function getLength(): ?int;

    /**
     * @param  mixed  $offset
     *
     * @return  mixed
     */
    public function offsetExists($offset);

    /**
     * @param  mixed  $offset
     *
     * @return  mixed
     */
    public function offsetGet($offset);

    /**
     * @param  mixed  $offset
     * @param  mixed  $value
     *
     * @return  void
     */
    public function offsetSet($offset, $value);

    /**
     * @param  mixed  $offset
     *
     * @return  void
     */
    public function offsetUnset($offset);

    /**
     * @return  mixed
     */
    public function toArray();

    /**
     * @param  mixed  $name
     * @param  mixed  $quotedValue
     *
     * @return  mixed
     */
    public function getValue($name, $quotedValue = null);
}

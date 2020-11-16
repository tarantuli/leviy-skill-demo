<?php
namespace Shared\Entities\Interfaces;

use Shared\RestApi\Interfaces\CastableToArrayInterface;

interface EntityInterface extends CastableToArrayInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Returns whether this object already has its data
     *
     * @return  bool
     */
    public function hasData(): bool;

    /**
     * Returns the ID of this instance
     *
     * @return  int
     */
    public function id(): int;

    /**
     * @return  EntityProviderInterface
     */
    public function getProvider();

    /**
     * Changes the current state of this instance
     *
     * @param  int  $state
     *
     * @return  bool
     */
    public function setState(int $state): ?bool;

    /**
     * Returns the current state of this instance
     *
     * @return  int
     */
    public function getState(): int;
}

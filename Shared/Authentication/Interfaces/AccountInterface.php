<?php
namespace Shared\Authentication\Interfaces;

use Shared\Authentication\Services\Interfaces\AccountRoleInterface;
use Shared\Entities\Interfaces\EntityInterface;

interface AccountInterface extends EntityInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  string
     */
    public function getAccessTokenKey(): string;

    /**
     * @return  bool
     */
    public function isActive(): bool;

    /**
     * @return  string
     */
    public function getEmailAddress(): string;

    /**
     * @return  int
     */
    public function id(): int;

    /**
     * @param  AccountRoleInterface|null  $role
     *
     * @return  bool
     */
    public function hasRole(?AccountRoleInterface $role): bool;
}

<?php
namespace Shared\Authentication\Exceptions;

use Shared\Authentication\Interfaces\AccountInterface;
use Shared\Exceptions\AbstractBaseException;

/**
 * (summary missing)
 */
class UserAccountExpiredException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * UserAccountExpiredException constructor
     *
     * @param  AccountInterface  $account
     * @param  string            $activeTill
     */
    public function __construct(AccountInterface $account, string $activeTill)
    {
        parent::__construct($account, $activeTill);
    }

    public function getPattern(): string
    {
        return 'User account %s is expired since %s';
    }
}

<?php
namespace Shared\Authentication\Interfaces;

interface AuthenticatorInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  string  $token
     *
     * @return  bool
     */
    public function isAccessTokenValid(string $token): bool;

    /**
     * @param  string  $token
     *
     * @return  AccountInterface|null
     */
    public function getAccountFromToken(string $token): ?AccountInterface;

    /**
     * @param  string  $token
     *
     * @return  FrontendInterface|null
     */
    public function getFrontendFromToken(string $token): ?FrontendInterface;

    /**
     * Tells the authenticator that we are done, and that it can save its caches
     *
     * @return  void
     */
    public function saveCaches(): void;
}

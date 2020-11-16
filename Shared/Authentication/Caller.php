<?php
namespace Shared\Authentication;

use Shared\Logging\Interfaces\ErrorLoggerInterface;

/**
 * A central reference to the connected account, independent of connection method
 */
class Caller
{
    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  Interfaces\AccountInterface
     */
    private static $account;

    /**
     * @var  Interfaces\FrontendInterface
     */
    private static $frontend;

    /**
     * @var  ErrorLoggerInterface
     */
    private static $logger;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Stores the connected account
     *
     * @param  Interfaces\AccountInterface  $account
     *
     * @return  void
     */
    public static function setAccount(Interfaces\AccountInterface $account): void
    {
        self::$account = $account;

        if (self::$logger) {
            self::$logger->setCaller($account);
        }
    }

    /**
     * Returns the connected account
     *
     * @return  Interfaces\AccountInterface
     */
    public static function getAccount(): Interfaces\AccountInterface
    {
        return self::$account;
    }

    /**
     * Returns the connected account ID
     *
     * @return  int|null
     */
    public static function getAccountId(): ?int
    {
        return self::$account ? self::$account->id() : null;
    }

    /**
     * Stores the connected frontend
     *
     * @param  Interfaces\FrontendInterface  $frontend
     *
     * @return  void
     */
    public static function setFrontend(Interfaces\FrontendInterface $frontend): void
    {
        self::$frontend = $frontend;

        if (self::$logger) {
            self::$logger->setCallerFrontend($frontend);
        }
    }

    /**
     * Returns the connected frontend
     *
     * @return  Interfaces\FrontendInterface
     */
    public static function getFrontend(): Interfaces\FrontendInterface
    {
        return self::$frontend;
    }

    /**
     * Returns the connected frontend ID
     *
     * @return  int|null
     */
    public static function getFrontendId(): ?int
    {
        return self::$frontend ? self::$frontend->id() : null;
    }

    /**
     * @param  ErrorLoggerInterface  $logger
     */
    public static function setLogger(ErrorLoggerInterface $logger)
    {
        self::$logger = $logger;
    }
}

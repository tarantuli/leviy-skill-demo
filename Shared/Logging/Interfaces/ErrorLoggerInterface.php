<?php
namespace Shared\Logging\Interfaces;

use Shared\Authentication\Interfaces\AccountInterface;
use Shared\Authentication\Interfaces\FrontendInterface;

interface ErrorLoggerInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  array  $trace
     */
    public function setBacktrace(array $trace): void;

    /**
     * @param  AccountInterface  $account
     */
    public function setCaller(AccountInterface $account): void;

    /**
     * @param  FrontendInterface  $frontend
     */
    public function setCallerFrontend(FrontendInterface $frontend): void;

    /**
     * @param  int     $code
     * @param  string  $message
     */
    public function error(int $code, string $message): void;

    /**
     * @param  callable  $callable
     */
    public function setErrorHandler(callable $callable): void;

    /**
     * @param  array  $getInformation
     */
    public function setRequestInformation(array $getInformation): void;

    /**
     * @param  array  $getAllParameters
     */
    public function setRequestParameters(array $getAllParameters): void;
}

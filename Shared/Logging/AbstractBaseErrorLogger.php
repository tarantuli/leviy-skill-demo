<?php
namespace Shared\Logging;

use Shared\Authentication\Interfaces\AccountInterface;
use Shared\Authentication\Interfaces\FrontendInterface;
use Shared\Providers\AbstractSingletonProvider;

abstract class AbstractBaseErrorLogger extends AbstractSingletonProvider implements Interfaces\ErrorLoggerInterface
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  AccountInterface
     */
    private $caller;

    /**
     * @var  FrontendInterface
     */
    private $callerFrontend;

    /**
     * @var  callable
     */
    private $errorHandler;

    /**
     * @var  array
     */
    private $givenBacktrace;

    /**
     * @var  mixed[]
     */
    private $requestInformation;

    /**
     * @var  mixed[]
     */
    private $requestParameters;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Changes the backtrace to use with the next logging action
     *
     * @param  array  $givenBacktrace
     */
    public function setBacktrace(array $givenBacktrace): void
    {
        $this->givenBacktrace = $givenBacktrace;
    }

    /**
     * Returns the backtrace to use with the next logging action. It defaults to the
     * result of debug_backtrace()
     *
     * @return  array
     */
    public function getBacktrace()
    {
        return $this->givenBacktrace ?: debug_backtrace();
    }

    /**
     * @param  AccountInterface  $caller
     *
     * @return  void
     */
    public function setCaller(AccountInterface $caller): void
    {
        $this->caller = $caller;
    }

    /**
     * @return  AccountInterface|null
     */
    public function getCaller(): ?AccountInterface
    {
        return $this->caller;
    }

    /**
     * @param  FrontendInterface  $frontend
     *
     * @return  void
     */
    public function setCallerFrontend(FrontendInterface $frontend): void
    {
        $this->callerFrontend = $frontend;
    }

    /**
     * @return  FrontendInterface|null
     */
    public function getCallerFrontend(): ?FrontendInterface
    {
        return $this->callerFrontend;
    }

    public function callErrorHandler(int $messageCode, string $message): void
    {
        if (!$this->errorHandler) {
            return;
        }

        $errorHandler = $this->errorHandler;

        call_user_func($errorHandler, ['messageCode' => $messageCode, 'message' => $message]);
    }

    /**
     * @param  callable  $errorHandler
     *
     * @return  void
     */
    public function setErrorHandler(callable $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param  array  $requestInformation
     *
     * @return  void
     */
    public function setRequestInformation(array $requestInformation): void
    {
        $this->requestInformation = $requestInformation;
    }

    /**
     * @return  array|null
     */
    public function getRequestInformation(): ?array
    {
        return $this->requestInformation;
    }

    /**
     * @param  array  $requestParameters
     *
     * @return  void
     */
    public function setRequestParameters(array $requestParameters): void
    {
        $this->requestParameters = $requestParameters;
    }

    /**
     * @return  array|null
     */
    public function getRequestParameters(): ?array
    {
        return $this->requestParameters;
    }
}

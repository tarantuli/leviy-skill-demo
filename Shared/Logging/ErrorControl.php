<?php
namespace Shared\Logging;

use Shared\Exceptions\ErrorException;
use Shared\Providers\AbstractSingletonProvider;
use Throwable;

/**
 * (summary missing)
 */
class ErrorControl extends AbstractSingletonProvider
{
    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  self
     */
    private static $instance;


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  Interfaces\ErrorLoggerInterface
     */
    private $logger;

    /**
     * @var  string
     */
    private $workingDirectory;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Initializes error control. Normalizes settings, sets the error log file and
     * registers handlers
     */
    public function __construct()
    {
        // Report all errors
        error_reporting(E_ALL);

        // Don't display errors
        ini_set('display_errors', false);

        // Register custom handlers
        set_error_handler([$this,          'errorHandler']);
        set_exception_handler([$this,      'exceptionHandler']);
        register_shutdown_function([$this, 'fatalErrorHandler']);
        self::$instance = $this;
    }

    /**
     * Handles an error trigger
     *
     * @param  int     $severity
     * @param  string  $message
     * @param  string  $file
     * @param  int     $line
     *
     * @return  void
     *
     * @throws  ErrorException
     */
    public function errorHandler(/** @noinspection PhpUnusedParameterInspection */ int $severity, string $message, string $file, int $line)
    {
        // Stop processing if errors were suppressed using "@"
        // (see http://www.php.net/manual/en/language.operators.errorcontrol.php#98895)
        if (0 == error_reporting()) {
            return;
        }

        throw new ErrorException($message, $file, $line);
    }

    /**
     * @param  Interfaces\ErrorLoggerInterface  $logger
     */
    public function setErrorLogger(Interfaces\ErrorLoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Handles the uncaught exception
     *
     * @param  Throwable  $exception
     *
     * @return  void
     */
    public function exceptionHandler(Throwable $exception)
    {
        // Add the source to the stacktrace
        $trace = $exception->getTrace();

        array_unshift($trace, ['file' => $exception->getFile(), 'line' => $exception->getLine()]);

        if ($this->logger) {
            $this->logger->setBacktrace($trace);
            $this->logger->error($exception->getCode(), $exception->getMessage());
        }
        else {
            echo $exception->getMessage(), LF;

            (new BacktracePrinter($trace))->go();

            // Turn off output buffering and flush whatever was buffered
            if (ob_get_level()) {
                ob_end_flush();
            }

            // Wait for input from the user if possible
            if (defined('STDIN')) {
                echo "\nDruk op een toets om af te sluiten...\n";

                fgets(STDIN);
            }
        }

        die();
    }

    /**
     * Handles fatal error triggers
     *
     * @return  void
     */
    public function fatalErrorHandler()
    {
        if (!$error = error_get_last()) {
            return;
        }

        if ($this->workingDirectory) {
            chdir($this->workingDirectory);
        }

        if ($this->logger) {
            $this->logger->setBacktrace([$error]);
            $this->logger->error($error['type'], $error['message']);
        }
        else {
            var_dump($error);
        }
    }

    /**
     * Store the working directory, to restore it when handling fatal errors
     *
     * @param  string  $workingDirectory
     *
     * @return  void
     */
    public function setWorkingDirectory(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }
}

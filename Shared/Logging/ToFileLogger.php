<?php
namespace Shared\Logging;

use ReflectionClass;
use Shared\Backend\Mode;
use Shared\DataControl\IpAddress;
use Shared\DataControl\Variable;
use Shared\Exceptions\InvalidInputException;
use Shared\FileControl\Exceptions\DirectoryNotFoundException;
use Shared\FileControl\File;
use Shared\Http\RequestData;
use Shared\System\Process;

/**
 * (summary missing)
 */
class ToFileLogger extends AbstractBaseErrorLogger
{
    /*****************
     *   Constants   *
     ****************/

    public const TARGET_FILE_TYPE_DATE = 2;
    public const TARGET_FILE_TYPE_SOURCE_LINE = 1;

    private const ALLOWED_TARGET_FILE_TYPES = [
        self::TARGET_FILE_TYPE_SOURCE_LINE,
        self::TARGET_FILE_TYPE_DATE,
    ];


    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  self
     */
    private static $instance;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Finalizes the log class by closing any remaining open file handles
     */
    public static function finalize()
    {
        if (!self::$instance) {
            return;
        }

        foreach (self::$instance->fileHandles as $fileHandle) {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        }
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  resource
     */
    private $currentFileHandle;

    /**
     * Default error message if any print if we're not allowed to print errors
     *
     * @var  string
     */
    private $defaultErrorMessage = null;

    /**
     * @var  string
     */
    private $fileDirectory;

    /**
     * @var  resource[]
     */
    private $fileHandles = [];

    /**
     * @var  string
     */
    private $lastBody;

    /**
     * @var  string
     */
    private $lastTitle;

    /**
     * Whether to print errors or not
     *
     * @var  bool
     */
    private $printErrors    = true;
    private $targetFileType = self::TARGET_FILE_TYPE_SOURCE_LINE;

    /**
     * @var  string
     */
    private $testingModeFileDirectory;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Initializes the log class, by registering the shutdown function
     *
     * @param  string       $fileDirectory
     * @param  string|null  $testingModeFileDirectory
     */
    public function __construct(string $fileDirectory, string $testingModeFileDirectory = null)
    {
        $this->fileDirectory = $fileDirectory;
        $this->testingModeFileDirectory = $testingModeFileDirectory ?: $fileDirectory;

        register_shutdown_function([static::class, 'finalize']);

        self::$instance = $this;
    }

    /**
     * Writes all given variables to the dump log
     *
     * @param  mixed  ...$variables  The variable values to log
     */
    public function dump(... $variables)
    {
        // Determine the calling script name and line
        $tracelines = $this->getBacktrace();
        $trace      = null;
        $sourceFile = null;

        foreach ($tracelines as $trace) {
            $sourceFile = Variable::keyval($trace, 'file', '[main]');

            if ($sourceFile === __FILE__) {
                continue;
            }

            break;
        }

        $sourceFile = str_replace(DIRECTORY_SEPARATOR, '/', $sourceFile);
        $sourceLineNumber = Variable::keyval($trace, 'line');

        try
        {
            $this->openLog('dump.{{ymd}}.log');
            $this->writeLine(0, sprintf('%s - %s:%s', date(DATETIME), $sourceFile, $sourceLineNumber));

            foreach ($variables as $i => $variable) {
                $stringValue = Variable::toString($variable, 10000, true);

                $this->writeLine(0, sprintf('  Var %u: %s', $i + 1, $stringValue));
            }

            $this->writeLine();
        }
        catch (DirectoryNotFoundException $exception)
        {
            var_dump($variables);
        }
    }

    /**
     * Registers a fatal error
     *
     * @param  int     $messageCode
     * @param  string  $message
     *
     * @return  void
     */
    public function error(int $messageCode, string $message): void
    {
        if (Process::areWeStuck()) {
            $this->dumpState();
        }

        // Save it
        $targetFile = $this->determineTargetFile();

        $this->saveMessage(sprintf('%u: %s', $messageCode, $message), $targetFile);

        $justTesting = Mode::inTestingMode();

        if ($justTesting) {
            // We're just testing
            return;
        }

        $this->callErrorHandler($messageCode, $message);

        if ($this->printErrors) {
            $this->printError();
        }
        elseif ($this->defaultErrorMessage) {
            echo $this->defaultErrorMessage;
        }
    }

    /**
     * Changes the directory where the log files will be stored
     *
     * @param  string  $fileDirectory
     *
     * @return  void
     */
    public function setFileDirectory(string $fileDirectory)
    {
        $this->fileDirectory = $fileDirectory;

        // Set the error log
        $errorLogFile = sprintf(
            '%s/php_errors.%s.log',
            $fileDirectory,
            date('Ymd', RequestData::get()->getRequestTime())
        );

        ini_set('error_log', $errorLogFile);
    }

    /**
     * Returns the file directory
     *
     * @return  string
     */
    public function getFileDirectory()
    {
        return $this->fileDirectory;
    }

    /**
     * Do print errors
     *
     * @return  void
     */
    public function doPrintErrors()
    {
        $this->printErrors = true;
    }

    /**
     * Don't print errors
     *
     * @param  string|null  $defaultMessage
     *
     * @return  void
     */
    public function dontPrintErrors(string $defaultMessage = null)
    {
        $this->printErrors = false;
        $this->defaultErrorMessage = $defaultMessage;
    }

    public function setTargetFileType(int $type): void
    {
        if (!in_array($type, self::ALLOWED_TARGET_FILE_TYPES)) {
            throw new InvalidInputException($type, 'target file type');
        }

        $this->targetFileType = $type;
    }

    public function trace()
    {
        $this->openLog('dump.{{ymd}}.log');
        $this->writeBacktraceInformation();
    }

    /**
     * @return  mixed[]
     */
    private function determineSourceFile()
    {
        foreach ($this->getBacktrace() as $trace) {
            $file = Variable::keyval($trace, 'file');

            if (!$file) {
                continue;
            }

            if (dirname($file) === __DIR__) {
                continue;
            }

            return [$file, $trace['line']];
        }

        return [false, false];
    }

    private function determineTargetFile(): string
    {
        switch ($this->targetFileType) {
            case self::TARGET_FILE_TYPE_DATE:
                return $this->getTargetFileDate();

            case self::TARGET_FILE_TYPE_SOURCE_LINE:
            default:
                return $this->getTargetFileSourceLine();
        }
    }

    /**
     * Dump the state of the system when the log was called without initialization
     *
     * @return  void
     */
    private function dumpState()
    {
        $fh = fopen('dump.log', 'a');

        fwrite($fh, var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true) . LF);
        fclose($fh);

        die('Failed to process error correctly');
    }

    /**
     * Opens a log file for writing
     *
     * @param  string  $file  The file name
     */
    private function openLog(string $file)
    {
        if (!$file) {
            $file = 'undefined.log';
        }

        if (!array_key_exists($file, $this->fileHandles)) {
            $filename = str_replace('{{ts}}', RequestData::get()->getRequestTime(), $file);
            $filename = str_replace('{{ymd}}', date('Ymd', RequestData::get()->getRequestTime()), $filename);

            $filePath = sprintf(
                '%s/%s',
                Mode::inTestingMode() ? $this->testingModeFileDirectory : $this->fileDirectory,
                $filename
            );

            File::ensureExistence($filePath);

            $this->fileHandles[$file] = fopen($filePath, 'ab');
        }

        $this->currentFileHandle = $this->fileHandles[$file];
        $this->lastBody = '';
    }

    /**
     * Print the error
     *
     * @return  void
     */
    private function printError()
    {
        if (!ON_CLI) {
            echo '<p style="white-space: pre-wrap; font-family: monospace">';
            echo htmlspecialchars($this->lastBody);
            echo '</p>';
        }
        else {
            echo $this->lastBody;
            echo 'Press any key to continue', LF;

            fgets(STDIN);
        }
    }

    /**
     * Writes a message to the given logfile, including additional information
     *
     * @param  string  $message
     * @param  string  $logfile  The name of the log file
     */
    private function saveMessage(string $message, string $logfile)
    {
        // Stop if we're stuck in a loop
        if (Process::areWeStuck()) {
            die($message);
        }

        $message = Variable::toSafeString($message);
        $this->lastTitle = $message;

        // Open logfile
        try
        {
            $this->openLog($logfile);

            // Write date, time and message
            $this->writeLine(0, date(DATETIME));
            $this->writeLine(1, $message);

            // Write caller and frontend data
            $this->writeCallerAndFrontendInformation();

            // Write request information
            $this->writeRequestInformation();

            // Write backtrace information
            $this->writeBacktraceInformation();

            // Close the entry
            $this->writeLine(0, str_repeat('--=', 35) . '--');
            $this->writeLine();
        }
        catch (DirectoryNotFoundException $exception)
        {
            var_dump($message);
        }
    }

    private function getTargetFileDate(): string
    {
        return sprintf('%s.log', date('Y-m-d'));
    }

    private function getTargetFileSourceLine(): string
    {
        [$file, $line] = $this->determineSourceFile();

        $path     = pathinfo($file, PATHINFO_DIRNAME);
        $path     = str_replace(BACKSLASH, SLASH, $path);
        $path     = explode(SLASH, $path);
        $path     = array_slice($path, -2);
        $path     = implode('-', $path) . '-' . pathinfo($file, PATHINFO_FILENAME);
        $filepart = preg_replace('/[^a-z0-9]+/', '-', strtolower($path));

        return sprintf('%s.%u.log', $filepart, $line);
    }

    /**
     * @return  void
     */
    private function writeBacktraceInformation()
    {
        $this->writeLine();
        $this->writeLine(0, 'BACKTRACE');

        $tracelines = $this->getBacktrace();

        foreach ($tracelines as $trace) {
            $this->writeTraceline($trace);
        }
    }

    /**
     * @return  void
     */
    private function writeCallerAndFrontendInformation()
    {
        $this->writeLine();
        $this->writeLine(0, 'CALLER AND FRONTEND');

        if ($this->getCaller()) {
            $this->writeLine(
                1,
                sprintf('Account:  %s (ID %u)', $this->getCaller()->getEmailAddress(), $this->getCaller()->id())
            );
        }
        else {
            $this->writeLine(1, 'No account logged on');
        }

        if ($this->getCallerFrontend()) {
            $this->writeLine(
                1,
                sprintf(
                    'Frontend: %s (ID %u)',
                    $this->getCallerFrontend()->getName(),
                    $this->getCallerFrontend()->id()
                )
            );
        }
        elseif (ON_CLI) {
            $this->writeLine(1, 'Called from the command line');
        }
        else {
            $this->writeLine(1, 'Not called by frontend or the command line');
        }
    }

    /**
     * Writes a single line to the log file
     *
     * @param  int|null     $indentLevel  The indent level
     * @param  string|null  $line         The line to write
     *
     * @return  int  The amount of bytes written
     */
    private function writeLine(int $indentLevel = null, string $line = null)
    {
        if ($indentLevel === null) {
            $line = LF;
        }
        else {
            $line = Variable::toSafeString($line);
            $line = str_repeat(TAB, $indentLevel) . $line . LF;
        }

        $this->lastBody .= $line;

        return fwrite($this->currentFileHandle, $line);
    }

    /**
     * @return  void
     */
    private function writeRequestInformation()
    {
        if (!$this->getRequestInformation()) {
            return;
        }

        $info   = $this->getRequestInformation();
        $params = $this->getRequestParameters();

        // Build content
        $this->writeLine();
        $this->writeLine(0, 'REST REQUEST');

        try
        {
            $this->writeLine(
                1,
                sprintf('Source:  %s:%u', IpAddress::binaryToString($info['sourceAddress']), $info['sourcePort'])
            );
        }
        catch (InvalidInputException $exception)
        {
            // Do nothing
        }

        $this->writeLine(
            1,
            sprintf(
                'Request: %s %s',
                $info['method'],
                sprintf('/v%u/%s', $info['version'], $info['resourcePath'])
            )
        );

        if ($params) {
            $this->writeLine(1, 'Query params:');

            foreach ($params as $key => $value) {
                $this->writeLine(2, sprintf('%s: %s', $key, Variable::toString($value)));
            }
        }

        $this->writeLine(
            1,
            sprintf('Body:    %s', $info['body'] == '' ? '< empty >' : Variable::toString($info['body']))
        );
        $this->writeLine(1, 'Headers:');

        foreach ($info['headers'] as $key => $value) {
            if (in_array($key, ['Accept', 'Accept-Encoding'])) {
                continue;
            }

            $this->writeLine(2, sprintf('%s %s', str_pad($key . ':', 16), $value));
        }

        $repeatData = [
            'method'  => $info['method'],
            'url'     => RequestData::get()->getRequestedUrl(),
            'body'    => $info['body'],
            'headers' => $info['headers']
        ];

        $this->writeLine();
        $this->writeLine(0, 'REPEAT DATA');
        $this->writeLine(0, json_encode($repeatData));
    }

    /**
     * @param  array  $trace
     *
     * @return  void
     */
    private function writeTraceline(array $trace)
    {
        $file       = Variable::keyval($trace, 'file', '[main]');
        $lineNumber = Variable::keyval($trace, 'line');
        $function   = Variable::keyval($trace, 'function');
        $class      = Variable::keyval($trace, 'class');
        $object     = Variable::keyval($trace, 'object');
        $type       = Variable::keyval($trace, 'type');
        $args       = Variable::keyval($trace, 'args', []);

        // File and line number
        $this->writeLine(1, $file . ':' . $lineNumber);

        // Method or function
        $id = $object && method_exists($object, 'id') ? '(' . $object->id() . ')' : '';
        $pseudoArgs     = [];
        $classReflector = $class ? new ReflectionClass($class) : null;

        $methodReflector = $classReflector && $classReflector->hasMethod($function)
            ? $classReflector->getMethod($function)
            : null;

        $parameterReflectors = $methodReflector ? $methodReflector->getParameters() : null;

        foreach ($args as $i => $argument) {
            $pseudoArgs[$i] = ($i + 1);

            if ($parameterReflectors && array_key_exists($i, $parameterReflectors)) {
                $pseudoArgs[$i] .= sprintf(' = %s', $parameterReflectors[$i]->name);
            }
        }

        $pseudoArgs = implode(', ', $pseudoArgs);

        if ($function) {
            $this->writeLine(2, $class . $id . $type . $function . '(' . $pseudoArgs . ')');
        }
        elseif ($class) {
            $this->writeLine(2, $class . $id);
        }

        // Arguments
        foreach ($args as $i => $argument) {
            $length = is_array($argument)
                ? count($argument)
                : (is_string($argument) ? strlen($argument) : null);

            if ($length === null) {
                $this->writeLine(3, ($i + 1) . ': ' . Variable::toString($argument, 1000));
            }
            else {
                $this->writeLine(3, ($i + 1) . ': <' . $length . '> ' . Variable::toString($argument, 1000));
            }
        }

        $this->writeLine();
    }
}

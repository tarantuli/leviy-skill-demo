<?php
namespace Shared;

use Exception;
use ReflectionClass;

/**
 * Contains general methods for working with this backend
 */
class Shared
{
    /************************
     *   Static variables   *
     ***********************/

    private static ?self $instance = null;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @return  self
     */
    public static function get()
    {
        if (self::$instance === null) {
            new self();
        }

        return self::$instance;
    }

    /**
     * Returns the default database connection
     *
     * @return  Databases\Interfaces\ServerInterface|null
     */
    public static function db(): ?Databases\Interfaces\ServerInterface
    {
        return self::get()->defaultDatabaseConnection;
    }

    /**
     * Deletes all uploaded files that weren't handled elsewhere
     *
     * @return  void
     */
    public static function deleteUploadedFiles(): void
    {
        foreach ($_FILES as $file) {
            if (file_exists($file['tmp_name'])) {
                unlink($file['tmp_name']);
            }
        }
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  string[]
     */
    private ?array $classFiles = null;

    private Databases\Interfaces\ServerInterface $defaultDatabaseConnection;

    /**
     * @var  string[]
     */
    private array $interfaceFiles;
    private Autoload\ClassLoader $loader;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Initializes the Shared library
     *
     * @return  void
     */
    public function __construct()
    {
        $this->ensureServerSettings();
        $this->ensureCommonConstants();
        $this->registerAutoLoader();
        $this->initializeLogs();

        register_shutdown_function('\Shared\deleteUploadedFiles');
        register_shutdown_function('\Shared\deleteTemporaryFiles');
        self::$instance = $this;
    }

    /**
     * Returns the autoloader used by this library
     *
     * @return  Autoload\ClassLoader
     */
    public function getAutoloader(): Autoload\ClassLoader
    {
        return $this->loader;
    }

    /**
     * Returns the names of classes that implement the given interface
     *
     * @param  string  $interface
     * @param  bool    $includeAbstract
     *
     * @return  string[]
     *
     * @throws  Reflection\Exceptions\InterfaceNotFoundException
     */
    public function getClassesImplementing(string $interface, bool $includeAbstract = false): array
    {
        if (!interface_exists($interface)) {
            throw new Reflection\Exceptions\InterfaceNotFoundException($interface);
        }

        $classes = [];

        foreach ($this->getClassFiles() as $class => $file) {
            try
            {
                $reflector = (new ReflectionClass($class));
            }
            catch (Exception $e)
            {
                continue;
            }

            if ($reflector->isInterface()) {
                continue;
            }

            if (!$reflector->implementsInterface($interface)) {
                continue;
            }

            if (!$includeAbstract && $reflector->isAbstract()) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Return an array of paths to the class declaration files, with class names as
     * keys
     *
     * @return  string[]
     */
    public function getClassFiles(): array
    {
        if ($this->classFiles !== null) {
            return $this->classFiles;
        }

        $classFiles = [];

        foreach ($psr4Prefixes = $this->loader->getPrefixesPsr4() as $rootPrefix => $directories) {
            foreach ($directories as $directory) {
                $directory = FileControl\File::normalizePath($directory);
                $directoryLength = strlen($directory);

                FileControl\Directory::ensureExistence($directory);

                foreach (FileControl\Directory::getIterator($directory, '\.phpc?$') as $file) {
                    $subdirectory = substr($file, $directoryLength + 1, -4);
                    $class = $this->getClassFromRootAndSubdirectory($rootPrefix, $subdirectory);

                    if (!array_key_exists($class, $classFiles)) {
                        $classFiles[$class] = (string) $file;
                    }
                }
            }
        }

        $this->classFiles = $classFiles;

        return $classFiles;
    }

    /**
     * @param  string  $rootPrefix
     * @param  string  $subdirectory
     *
     * @return  string
     */
    public function getClassFromRootAndSubdirectory(string $rootPrefix, string $subdirectory): string
    {
        return sprintf('%s%s', $rootPrefix, str_replace(DIRECTORY_SEPARATOR, BACKSLASH, $subdirectory));
    }

    /**
     * Changes the default database connection
     *
     * @param  Databases\Interfaces\ServerInterface  $connection
     *
     * @return  void
     */
    public function setDefaultDatabaseConnection(Databases\Interfaces\ServerInterface $connection): void
    {
        $this->defaultDatabaseConnection = $connection;
    }

    /**
     * Returns all files that are interfaces
     *
     * @return  string[]
     */
    public function getInterfaceFiles(): array
    {
        if ($this->interfaceFiles !== null) {
            return $this->interfaceFiles;
        }

        $interfaceFiles = [];

        foreach ($this->getClassFiles() as $class => $file) {
            $reflector = new ReflectionClass($class);

            if ($reflector->isInterface()) {
                $interfaceFiles[$class] = $file;
            }
        }

        $this->interfaceFiles = $interfaceFiles;

        return $interfaceFiles;
    }

    /**
     * Registers a namespace and its paths with the autoloader
     *
     * @param  string    $namespace
     * @param  string[]  $paths
     *
     * @return  void
     */
    public function addNamespace(string $namespace, array $paths): void
    {
        $this->loader->setPsr4(sprintf('%s%s', $namespace, BACKSLASH), $paths);
    }

    /**
     * Registers namespaces and their paths
     *
     * @param  array  $namespaces
     */
    public function addNamespaces(array $namespaces): void
    {
        foreach ($namespaces as $namespace => $paths) {
            $this->addNamespace($namespace, $paths);
        }
    }

    /**
     * Returns the registered namespaces
     *
     * @return  array
     */
    public function getNamespaces(): array
    {
        return array_keys($this->loader->getPrefixesPsr4());
    }

    /**
     * Returns a possible definition filepath for the given class name
     *
     * @param  string  $class
     *
     * @return  string|null
     */
    public function suggestFilepath(string $class): ?string
    {
        return $this->loader->suggestFilepath($class);
    }

    /**
     * Ensures that common constants like LF for a newline are defined
     *
     * @return  void
     */
    private function ensureCommonConstants(): void
    {
        $constants = [
            'ON_CLI'       => PHP_SAPI === 'cli',
            'NUL_BYTE'     => "\0",
            'CR'           => "\r",
            'CRLF'         => "\r\n",
            'LF'           => "\n",
            'LFLF'         => "\n\n",
            'TAB'          => "\t",
            'VERTICAL_TAB' => "\v",
            'QUOTE'        => '"',
            'APOS'         => "'",
            'BACKTICK'     => '`',
            'BACKSLASH'    => '\\',
            'SLASH'        => '/',
            'PIPE'         => '|',
            'DATETIME'     => 'Y-m-d H:i:s',
            'REQUEST_DATETIME'
                           => date('Y-m-d H:i:s'),
            'REQUEST_DATE' => date('Y-m-d'),
            'REQUEST_TIME' => date('H:i:s'),
            'START_OF_TODAY'
                           => date('Y-m-d 00:00:00'),
            'END_OF_TODAY' => date('Y-m-d 23:59:59'),
            'ONE_DAY'      => 86400,
            'ONE_WEEK'     => 604800,
            'ONE_YEAR'     => 220752000,
            'GIGABYTE'     => 1073741824,
            'MEGABYTE'     => 1048576,
            'KILOBYTE'     => 1024,
            'BINARY_BYTES' => "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F",
        ];

        foreach ($constants as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    /**
     * Ensures that certain settings, like internal encoding and the timezome, are
     * set
     *
     * @return  void
     */
    private function ensureServerSettings(): void
    {
        // Set the internal (and therefore default) encoding of the multibyte functions to UTF-8
        mb_internal_encoding('UTF-8');

        // Set the timezone to Europe/Amsterdam
        date_default_timezone_set('Europe/Amsterdam');
    }

    /**
     * Initalizes the Log class
     *
     * @return  void
     */
    private function initializeLogs(): void
    {
        new Logging\ErrorControl();
    }

    /**
     * Registers an autoloader with the given namespaces, if any
     *
     * @param  string[]  $namespaces
     *
     * @return  void
     */
    private function registerAutoLoader(array $namespaces = []): void
    {
        /** @noinspection PhpIncludeInspection */
        require_once sprintf('%s/Autoload/ClassLoader.php', __DIR__);

        $this->loader = new Autoload\ClassLoader();

        $this->loader->setPsr4('Shared\\', [__DIR__]);

        foreach ($namespaces as $namespace => $paths) {
            $this->loader->setPsr4(sprintf('%s%s', $namespace, BACKSLASH), $paths);
        }

        $this->loader->register(true);
    }
}

/**
 * Non-class callback function to allow class references to be found
 */
function deleteUploadedFiles()
{
    Shared::deleteUploadedFiles();
}

/**
 * Non-class callback function to allow class references to be found
 */
function deleteTemporaryFiles()
{
    FileControl\TemporaryFile::delete();
}

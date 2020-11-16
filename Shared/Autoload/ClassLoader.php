<?php
namespace Shared\Autoload;

use InvalidArgumentException;
use Shared\FileControl\File;
use Shared\Json\Json;

/**
 * ClassLoader implements a PSR-0, PSR-4 and classmap class loader
 */
class ClassLoader
{
    /**************************
     *   Instance variables   *
     *************************/

    private $classMap = [];
    private $classMapAuthoritative = false;
    private $fallbackDirsPsr0      = [];
    private $fallbackDirsPsr4      = [];

    private $historyFile;

    private $prefixDirsPsr4    = [];
    private $prefixesPsr0      = [];
    private $prefixLengthsPsr4 = [];
    private $requestedClasses  = [];

    private $rootScript;

    private $useIncludePath = false;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  void
     */
    public function __destruct()
    {
        // Log requested classes
        if ($this->historyFile) {
            $history = json_decode(
                file_exists($this->historyFile) ? file_get_contents($this->historyFile) : '{}',
                JSON_OBJECT_AS_ARRAY
            );

            foreach ($this->requestedClasses as $class => $time) {
                $history[$this->rootScript][str_replace(BACKSLASH, SLASH, $class)] = $time;
            }

            File::ensureExistence($this->historyFile, Json::encode($history));
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix, either appending or
     * prepending to the ones previously set for this prefix
     *
     * @param  string        $prefix   The prefix
     * @param  array|string  $paths    The PSR-0 root directories
     * @param  bool          $prepend  Whether to prepend the directories
     *
     * @return  void
     */
    public function add(string $prefix, $paths, bool $prepend = false): void
    {
        if (!$prefix) {
            if ($prepend) {
                $this->fallbackDirsPsr0 = array_merge((array) $paths, $this->fallbackDirsPsr0);
            }
            else {
                $this->fallbackDirsPsr0 = array_merge($this->fallbackDirsPsr0, (array) $paths);
            }

            return;
        }

        $first = $prefix[0];

        if (!isset($this->prefixesPsr0[$first][$prefix])) {
            $this->prefixesPsr0[$first][$prefix] = (array) $paths;

            return;
        }

        if ($prepend) {
            $this->prefixesPsr0[$first][$prefix]
                 = array_merge((array) $paths, $this->prefixesPsr0[$first][$prefix]);
        }
        else {
            $this->prefixesPsr0[$first][$prefix]
                 = array_merge($this->prefixesPsr0[$first][$prefix], (array) $paths);
        }
    }

    /**
     * Registers a set of PSR-0 directories for a given prefix, replacing any others
     * previously set for this prefix
     *
     * @param  string        $prefix  The prefix
     * @param  array|string  $paths   The PSR-0 base directories
     */
    public function set(string $prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirsPsr0 = (array) $paths;
        }
        else {
            $this->prefixesPsr0[$prefix[0]][$prefix] = (array) $paths;
        }
    }

    /**
     * @param  array  $classMap  Class to filename map
     */
    public function addClassMap(array $classMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $classMap);
        }
        else {
            $this->classMap = $classMap;
        }
    }

    /**
     * @return  mixed
     */
    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * Turns off searching the prefix and fallback directories for classes that have
     * not been registered with the class map
     *
     * @param  bool  $classMapAuthoritative
     */
    public function setClassMapAuthoritative(bool $classMapAuthoritative)
    {
        $this->classMapAuthoritative = $classMapAuthoritative;
    }

    /**
     * Should class lookup fail if not found in the current class map?
     *
     * @return  bool
     */
    public function isClassMapAuthoritative(): bool
    {
        return $this->classMapAuthoritative;
    }

    /**
     * @return  mixed
     */
    public function getFallbackDirs()
    {
        return $this->fallbackDirsPsr0;
    }

    /**
     * @return  mixed
     */
    public function getFallbackDirsPsr4()
    {
        return $this->fallbackDirsPsr4;
    }

    /**
     * Finds the path to the file where the class is defined
     *
     * @param  string  $class  The name of the class
     *
     * @return  string|false  The path if found, false otherwise
     */
    public function findFile(string $class)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if (BACKSLASH == $class[0]) {
            $class = substr($class, 1);
        }

        // class map lookup
        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        }

        if ($this->classMapAuthoritative) {
            return false;
        }

        $file = $this->findFileWithExtension($class, '.php');

        // Search for Hack files if we are running on HHVM
        if ($file === null && defined('HHVM_VERSION')) {
            $file = $this->findFileWithExtension($class, '.hh');
        }

        if ($file === null) {
            // Remember that this class does not exist
            return $this->classMap[$class] = false;
        }

        return $file;
    }

    /**
     * @param  mixed  $historyFile
     * @param  mixed  $rootScript
     *
     * @return  void
     */
    public function setHistoryFile($historyFile, $rootScript): void
    {
        $this->rootScript = $rootScript = str_replace(BACKSLASH, SLASH, $rootScript);

        $history = json_decode(
            file_exists($historyFile) ? file_get_contents($historyFile) : '{}',
            JSON_OBJECT_AS_ARRAY
        );

        if (!is_array($history)) {
            $history = [];
        }

        if (!array_key_exists($rootScript, $history)) {
            $history[$rootScript] = [];

            if (file_exists($historyFile)) {
                File::ensureExistence($historyFile, Json::encode($history));
            }
        }

        $this->historyFile = $historyFile;
    }

    /**
     * Loads the given class or interface
     *
     * @param  string  $class  The name of the class
     *
     * @return  bool|null  True if loaded, null otherwise
     */
    public function loadClass(string $class): ?bool
    {
        if ($file = $this->findFile($class)) {
            $this->requestedClasses[$class] = time();

            includeFile($file);

            return true;
        }

        return null;
    }

    /**
     * @return  mixed
     */
    public function getPrefixes()
    {
        if (!empty($this->prefixesPsr0)) {
            return call_user_func_array('array_merge', $this->prefixesPsr0);
        }

        return [];
    }

    /**
     * @return  mixed
     */
    public function getPrefixesPsr4()
    {
        return $this->prefixDirsPsr4;
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace, either appending
     * or prepending to the ones previously set for this namespace
     *
     * @param  string        $prefix   The prefix/namespace, with trailing BACKSLASH
     * @param  array|string  $paths    The PSR-4 base directories
     * @param  bool          $prepend  Whether to prepend the directories
     *
     * @throws  InvalidArgumentException
     */
    public function addPsr4(string $prefix, $paths, bool $prepend = false)
    {
        if (!$prefix) {
            // Register directories for the root namespace
            if ($prepend) {
                $this->fallbackDirsPsr4 = array_merge((array) $paths, $this->fallbackDirsPsr4);
            }
            else {
                $this->fallbackDirsPsr4 = array_merge($this->fallbackDirsPsr4, (array) $paths);
            }
        }
        elseif (!isset($this->prefixDirsPsr4[$prefix])) {
            // Register directories for a new namespace
            $length = strlen($prefix);

            if (BACKSLASH !== $prefix[$length - 1]) {
                throw new InvalidArgumentException(
                    'A non-empty PSR-4 prefix must end with a namespace separator.'
                );
            }

            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = (array) $paths;
        }
        elseif ($prepend) {
            // Prepend directories for an already registered namespace
            $this->prefixDirsPsr4[$prefix] = array_merge((array) $paths, $this->prefixDirsPsr4[$prefix]);
        }
        else {
            // Append directories for an already registered namespace
            $this->prefixDirsPsr4[$prefix] = array_merge($this->prefixDirsPsr4[$prefix], (array) $paths);
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace, replacing any
     * others previously set for this namespace
     *
     * @param  string        $prefix  The prefix/namespace, with trailing BACKSLASH
     * @param  array|string  $paths   The PSR-4 base directories
     *
     * @throws  InvalidArgumentException
     */
    public function setPsr4(string $prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirsPsr4 = (array) $paths;
        }
        else {
            $length = strlen($prefix);

            if (BACKSLASH !== $prefix[$length - 1]) {
                throw new InvalidArgumentException(
                    'A non-empty PSR-4 prefix must end with a namespace separator.'
                );
            }

            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = (array) $paths;
        }
    }

    /**
     * Registers this instance as an autoloader
     *
     * @param  bool  $prepend  Whether to prepend the autoloader or not
     */
    public function register(bool $prepend = false)
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }

    /**
     * @param  mixed  $class
     *
     * @return  string|null
     */
    public function suggestFilepath($class): ?string
    {
        $prefix = null;
        $paths  = [];

        foreach ($this->prefixDirsPsr4 as $prefix => $paths) {
            if (substr($class, 0, $this->prefixLengthsPsr4[$prefix[0]][$prefix]) === $prefix) {
                break;
            }
        }

        if (!array_key_exists(0, $paths)) {
            return null;
        }

        $path = $paths[0];

        return sprintf('%s%s%s.php', $path, DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    }

    /**
     * Unregisters this instance as an autoloader
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Turns on searching the include path for class files
     *
     * @param  bool  $useIncludePath
     */
    public function setUseIncludePath(bool $useIncludePath)
    {
        $this->useIncludePath = $useIncludePath;
    }

    /**
     * Can be used to check if the autoloader uses the include path to check for
     * classes
     *
     * @return  bool
     */
    public function getUseIncludePath(): bool
    {
        return $this->useIncludePath;
    }

    /**
     * @param  mixed  $class
     * @param  mixed  $ext
     *
     * @return  mixed
     */
    private function findFileWithExtension($class, $ext)
    {
        // PSR-4 lookup
        $logicalPathPsr4 = sprintf('%s%s', strtr($class, BACKSLASH, DIRECTORY_SEPARATOR), $ext);
        $first = $class[0];

        if (isset($this->prefixLengthsPsr4[$first])) {
            foreach ($this->prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($this->prefixDirsPsr4[$prefix] as $dir) {
                        $file = sprintf(
                            '%s%s%s',
                            $dir,
                            DIRECTORY_SEPARATOR,
                            substr($logicalPathPsr4, $length)
                        );

                        if (file_exists($file)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->fallbackDirsPsr4 as $dir) {
            if (file_exists($file = sprintf('%s%s%s', $dir, DIRECTORY_SEPARATOR, $logicalPathPsr4))) {
                return $file;
            }
        }

        // PSR-0 lookup
        if (false !== $pos = strrpos($class, BACKSLASH)) {
            // namespaced class name
            $logicalPathPsr0 = sprintf(
                '%s%s',
                substr($logicalPathPsr4, 0, $pos + 1),
                strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR)
            );
        }
        else {
            // PEAR-like class name
            $logicalPathPsr0 = sprintf('%s%s', strtr($class, '_', DIRECTORY_SEPARATOR), $ext);
        }

        if (isset($this->prefixesPsr0[$first])) {
            foreach ($this->prefixesPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (file_exists($file
                                    = sprintf('%s%s%s', $dir, DIRECTORY_SEPARATOR, $logicalPathPsr0)))
                        {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-0 fallback dirs
        foreach ($this->fallbackDirsPsr0 as $dir) {
            if (file_exists($file = sprintf('%s%s%s', $dir, DIRECTORY_SEPARATOR, $logicalPathPsr0))) {
                return $file;
            }
        }

        // PSR-0 include paths
        if ($this->useIncludePath && $file = stream_resolve_include_path($logicalPathPsr0)) {
            return $file;
        }

        return null;
    }
}

/**
 * Scope isolated include. Prevents access to $this/self from included files
 *
 * @param  string  $file
 */
function includeFile($file)
{
    /** @noinspection PhpIncludeInspection */
    include $file;
}

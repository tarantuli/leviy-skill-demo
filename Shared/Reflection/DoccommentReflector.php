<?php
namespace Shared\Reflection;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use Shared\DataControl\Variable;
use Shared\Exceptions\InvalidInputException;
use Shared\Php\InternalTypes;
use stdClass;

/**
 * (summary missing)
 */
class DoccommentReflector
{
    /*****************
     *   Constants   *
     ****************/

    const RETURN_OBJECT = 2;
    const RETURN_RAW    = 1;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @return  Closure
     */
    public static function isClassReference()
    {
        return function ($item)
        {
            if (in_array(strtolower($item), InternalTypes::IN_DOCCOMMENTS)) {
                return false;
            }

            return true;
        };
    }

    /**
     * Returns the ReflectionClass object for the given class
     *
     * @param  mixed  $class
     *
     * @return  ReflectionClass
     */
    public static function getClassReflector($class)
    {
        if ($class instanceof ReflectionClass) {
            return $class;
        }

        return new ReflectionClass($class);
    }

    /**
     * Returns the doccomment for the given class and method. It also looks in
     * interfaces, traits and parents
     *
     * @param  mixed  $class
     * @param  mixed  $method
     * @param  int    $returnType  1 for the raw string, 2 for a Doccomment instance
     *
     * @return  self|string
     */
    public static function forMethod($class, $method, int $returnType = self::RETURN_RAW)
    {
        $doccomments = '';

        // Get a method reflector
        $methodReflector = self::getMethodReflector($class, $method);

        // Get its doccomment
        if ($doccomment = $methodReflector->getDocComment()) {
            $doccomments .= $doccomment;
        }

        $methodName = $methodReflector->name;

        if ($classReflector = self::getClassReflector($class)) {
            // If no doccomment is found, try to find one in the class interfaces
            $interfaceReflectors = $classReflector->getInterfaces();

            foreach ($interfaceReflectors as $interfaceReflector) {
                if (!$interfaceReflector->hasMethod($methodName)) {
                    continue;
                }

                $interfaceMethodReflector = $interfaceReflector->getMethod($methodName);

                if ($doccomment = $interfaceMethodReflector->getDocComment()) {
                    $doccomments .= $doccomment;
                }
            }

            // If no doccomment is found, try to find one in the class traits
            $traitReflectors = $classReflector->getTraits();

            foreach ($traitReflectors as $traitReflector) {
                if (!$traitReflector->hasMethod($methodName)) {
                    continue;
                }

                $traitMethodReflector = $traitReflector->getMethod($methodName);

                if ($doccomment = $traitMethodReflector->getDocComment()) {
                    $doccomments .= $doccomment;
                }
            }

            // If still no doccomment is found, try to find one in the class parent
            $parentReflector = $classReflector->getParentClass();

            if ($parentReflector && $parentReflector->hasMethod($methodName)) {
                $parentMethodReflector = $parentReflector->getMethod($methodName);
                $doccomments .= self::forMethod($parentReflector, $parentMethodReflector, self::RETURN_RAW);
            }
        }

        return ($returnType === self::RETURN_RAW) ? $doccomments : new self($doccomments);
    }

    /**
     * Returns the ReflectionMethod object for the given class and method
     *
     * @param  mixed  $class
     * @param  mixed  $method
     *
     * @return  ReflectionMethod
     *
     * @throws  Exceptions\ClassMethodNotFoundException
     */
    public static function getMethodReflector($class, $method)
    {
        if ($method instanceof ReflectionMethod) {
            // It's already a method reflector
            return $method;
        }

        $classReflector = self::getClassReflector($class);
        $className      = $classReflector->name;

        if (method_exists($className, $method)) {
            return new ReflectionMethod($className, $method);
        }

        throw new Exceptions\ClassMethodNotFoundException($method, $className);
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  string
     */
    private $longdesc;

    /**
     * @var  string[]
     */
    private $params = [];

    /**
     * @var  string[]
     */
    private $perKeyword = [];

    /**
     * @var  string
     */
    private $raw;

    /**
     * @var  string[]
     */
    private $returns = [];

    /**
     * @var  string
     */
    private $summary;

    /**
     * @var  string[]
     */
    private $throws = [];

    /**
     * @var  string[]
     */
    private $todos = [];

    /**
     * @var  string[]
     */
    private $unknowns = [];

    /**
     * @var  string[]
     */
    private $varTypes = [];


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Constructor, also analyses the given raw value
     *
     * @param  string  $raw
     */
    public function __construct(string $raw)
    {
        $this->raw = $raw;

        $this->analyzeRaw();
    }

    /**
     * @return  array
     */
    public function getClassReferences()
    {
        $classes = array_merge(
            explode(PIPE, $this->getReturnType()),
            explode(PIPE, $this->getVariableType())
        );

        foreach ($this->getParameters() as $parameter) {
            $classes = array_merge($classes, explode(PIPE, $parameter->type));
        }

        foreach ($this->getThrows() as $thrown) {
            $classes[] = $thrown;
        }

        foreach ($classes as & $class) {
            if (substr($class, -2) === '[]') {
                $class = substr($class, 0, -2);
            }
        }

        return array_filter($classes, static::isClassReference());
    }

    /**
     * Returns whether the given keyword, e.g. 'forbidRestAccess', is present in the
     * doccomment
     *
     * @param  string  $keyword
     *
     * @return  bool
     */
    public function hasKeyword(string $keyword)
    {
        return Variable::hasKey($this->perKeyword, $keyword);
    }

    /**
     * Returns the long description
     *
     * @return  string
     */
    public function getLongDesc()
    {
        return $this->longdesc;
    }

    /**
     * Returns the parameters
     *
     * @return  array
     *
     * @throws  InvalidInputException
     */
    public function getParameters()
    {
        $retarray = [];

        foreach ($this->params as $param) {
            $data = explode(' ', $param);

            if (!Variable::hasKey($data, 1)) {
                if (!Variable::hasKey($data, 0)) {
                    throw new InvalidInputException($this->raw, 'doccomment');
                }
                else {
                    $data = ['mixed', $data[0]];
                }
            }

            if (!in_array(substr($data[1], 0, 1), ['$', '&', '.'])) {
                return [];
            }

            $obj        = new stdClass();
            $obj->name  = ltrim($data[1], '&$.');
            $obj->type  = $data[0];
            $obj->desc  = implode(' ', array_slice($data, 2));
            $retarray[] = $obj;
        }

        return $retarray;
    }

    /**
     * Returns the return description
     *
     * @return  string
     */
    public function getReturnDesc()
    {
        if (count($this->returns) == 0) {
            return null;
        }

        $return = explode(' ', $this->returns[0]);

        return implode(' ', array_slice($return, 1));
    }

    /**
     * Returns the return type
     *
     * @return  string
     */
    public function getReturnType()
    {
        if (count($this->returns) == 0) {
            return 'void';
        }

        $return = explode(' ', $this->returns[0]);

        return $return[0];
    }

    /**
     * Returns the summary
     *
     * @return  string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    public function getThrows()
    {
        return $this->throws;
    }

    public function getVariableType()
    {
        return explode(' ', Variable::keyval($this->varTypes, 0))[0];
    }

    /**
     * Analyses the raw string
     *
     * @return  void
     */
    private function analyzeRaw()
    {
        $lines           = explode(LF, $this->raw);
        $buildingSummary = true;
        $lastKeyword     = null;

        // Holders
        $longdesc   = null;
        $params     = [];
        $perKeyword = [];
        $returns    = [];
        $varTypes   = [];
        $throws     = [];
        $summary    = null;
        $todos      = [];
        $unknowns   = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (substr($line, 0, 3) == '/**') {
                $line = substr($line, 3);
            }

            if (substr($line, -2) == '*/') {
                $line = substr($line, 0, -2);
            }

            $line = trim($line);
            $line = ltrim($line, '* ');
            $line = preg_replace('/\s+/', ' ', $line);

            if (preg_match('#@(\w+)(?: (.+))?#', $line, $match)) {
                $keyword = $match[1];
                $content = Variable::keyval($match, 2);

                if ($keyword == 'param') {
                    $params[] = $content;
                }
                elseif ($keyword == 'return') {
                    $returns[] = $content;
                }
                elseif ($keyword == 'var') {
                    $varTypes[] = $content;
                }
                elseif ($keyword == 'throws') {
                    $throws[] = $content;
                }
                elseif ($keyword == 'todo') {
                    $todos[] = $content;
                }
                else {
                    $unknowns[] = $line;
                }

                $lastKeyword = $keyword;

                if (!Variable::hasKey($perKeyword, $keyword)) {
                    $perKeyword[$keyword] = [];
                }

                $perKeyword[$keyword][] = $content;

                continue;
            }

            if ($line == '') {
                $lastKeyword = null;

                if ($summary !== null) {
                    $buildingSummary = false;
                }

                continue;
            }

            if (substr($line, 0, 1) == '-') {
                $line = '<li>' . substr($line, 1) . '</li>';
            }

            if ($lastKeyword) {
                $line = ' ' . $line;

                if ($lastKeyword == 'param') {
                    $params[count($params) - 1] .= $line;
                }
                elseif ($lastKeyword == 'return') {
                    $returns[count($returns) - 1] .= $line;
                }
                elseif ($lastKeyword == 'var') {
                    $varTypes[count($varTypes) - 1] .= $line;
                }
                elseif ($lastKeyword == 'throws') {
                    $throws[count($throws) - 1] .= $line;
                }
                elseif ($lastKeyword == 'todo') {
                    $todos[count($todos) - 1] .= $line;
                }
                else {
                    $unknowns[count($unknowns) - 1] .= $line;
                }
            }
            elseif ($buildingSummary) {
                $summary .= $line . ' ';
            }
            else {
                $longdesc .= $line . ' ';
            }
        }

        $this->longdesc   = trim($longdesc);
        $this->params     = $params;
        $this->perKeyword = $perKeyword;
        $this->returns    = $returns;
        $this->varTypes   = $varTypes;
        $this->throws     = $throws;
        $this->summary    = trim($summary);
        $this->todos      = $todos;
        $this->unknowns   = $unknowns;
    }
}

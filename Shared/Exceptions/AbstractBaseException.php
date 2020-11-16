<?php
namespace Shared\Exceptions;

use Exception;
use Shared\DataControl\Str;
use Shared\DataControl\Variable;

/**
 * (summary missing)
 */
abstract class AbstractBaseException extends Exception
{
    /*********************************
     *   Abstract instance methods   *
     ********************************/

    /**
     * Returns the message pattern
     *
     * @return  string
     */
    abstract public function getPattern(): string;


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  string[]
     */
    private array $arguments;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new exception
     *
     * @param  mixed  ...$arguments  Additional Information that will be put in the child::PATTERN string
     */
    public function __construct(... $arguments)
    {
        $this->arguments = $arguments;
        $class = get_called_class();

        // XOR the class name into a code
        for ($i = 0, $code = 0; $i < strlen($class); ++$i) {
            $code ^= ord($class[$i]);
        }

        // Build the message
        $message = Str::sprintf($this->getPattern(), $arguments, [Variable::class, 'toString'], null);

        parent::__construct($message, $code);
    }

    /**
     * @param  int  $index
     *
     * @return  mixed
     */
    public function getArgument(int $index)
    {
        return Variable::keyval($this->arguments, $index);
    }

    /**
     * @return  mixed[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}

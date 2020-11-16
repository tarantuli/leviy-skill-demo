<?php
namespace Shared\Logging;

use Shared\DataControl\Variable;

class BacktracePrinter
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  array
     */
    private $trace;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * BacktracePrinter constructor
     *
     * @param  array  $trace
     */
    public function __construct(array $trace)
    {
        $this->trace = $trace;
    }

    public function go(): void
    {
        foreach ($this->trace as $item) {
            echo LF;

            printf("%s:%u\n", Variable::keyval($item, 'file'), Variable::keyval($item, 'line', 0));
            printf("   %s::%s()\n", Variable::keyval($item, 'class'), Variable::keyval($item, 'function'));
        }
    }
}

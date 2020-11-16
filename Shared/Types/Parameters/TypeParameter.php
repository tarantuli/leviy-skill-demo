<?php
namespace Shared\Types\Parameters;

/**
 * (summary missing)
 */
class TypeParameter
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  bool
     */
    public $isRequired;

    /**
     * @var  string
     */
    public $name;

    /**
     * @var  string
     */
    public $type;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Constructs a new TypeParameter instance
     *
     * @param  string  $name
     * @param  string  $type
     * @param  bool    $isRequired
     */
    public function __construct(string $name, string $type, bool $isRequired)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isRequired = $isRequired;
    }

    /**
     * Returns the name of this parameter
     *
     * @return  string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns whether this parameter is required or not
     *
     * @return  bool
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * Returns the type of this parameter
     *
     * @return  string
     */
    public function getType()
    {
        return $this->type;
    }
}

<?php
namespace Shared\RestApi;

use Shared\DataControl\Variable;
use Shared\Entities\ParameterValue;

class RecursivePropertySetter
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  RequestProcessor
     */
    private $request;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * RecursivePropertySetter constructor
     *
     * @param  RequestProcessor  $request
     */
    public function __construct(RequestProcessor $request)
    {
        $this->request = $request;
    }

    /**
     * @param  Interfaces\ResourceInterface  $instance
     * @param  array                         $values
     */
    public function setPropertyValues(Interfaces\ResourceInterface $instance, array $values)
    {
        $properties = $instance->getRestProperties();

        foreach ($values as $propertyName => $value) {
            if (!isset($properties[$propertyName])) {
                continue;
            }

            if (!$setter = Variable::keyval($properties[$propertyName], 'setter')) {
                continue;
            }

            $this->resolveEmbeddedInstance($value);

            ParameterValue::normalize($value, $instance, $setter);

            $instance->$setter($value);
        }

        if ($instance instanceof Interfaces\AfterSettingPropertiesInterface) {
            $instance->runAfterSettingProperties();
        }
    }

    /**
     * @param  mixed  &$value
     */
    private function resolveEmbeddedInstance(& $value): void
    {
        if ($embeddedInstance = Variable::keyval($value, 'href')) {
            $this->request->urlToObject($embeddedInstance);

            if ($embeddedInstance) {
                $this->setPropertyValues($embeddedInstance, $value);

                $value = $embeddedInstance;
            }
        }
    }
}

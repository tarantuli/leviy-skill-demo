<?php
namespace Settings;

class GlobalSettings
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @return  string[]
     */
    public function additionalNamespaces(): array
    {
        return [
            'Project'  => ['Project'],
            'Settings' => ['Settings'],
        ];
    }
}

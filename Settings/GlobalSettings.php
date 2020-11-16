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

    /**
     * @return  string[]
     */
    public function pathToClassMapping(): array
    {
        return [];
    }

    /**
     * @return  string[]
     */
    public function publicApiPaths(): array
    {
        return [];
    }
}

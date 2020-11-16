<?php
namespace Shared\Providers;

use ReflectionClass;
use Shared\DataControl\Variable;
use Shared\Entities;

class ProviderRegistry extends AbstractSingletonProvider
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  Entities\Interfaces\EntityProviderInterface[]
     */
    private $entityProviders = [];


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  string  $entityClass
     *
     * @return  Entities\Interfaces\EntityProviderInterface
     */
    public function forEntityClass(string $entityClass): ?Entities\Interfaces\EntityProviderInterface
    {
        $entityClass = ltrim($entityClass, BACKSLASH);

        if (!Variable::hasKey($this->entityProviders, $entityClass)) {
            // Don't throw an exception, that's too expensive for how often this can trigger in one call
            return null;
        }

        if (is_string($this->entityProviders[$entityClass])) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->entityProviders[$entityClass] = $this->entityProviders[$entityClass]::get();
        }

        return $this->entityProviders[$entityClass];
    }

    /**
     * @param  string                                              $entityClass
     * @param  Entities\Interfaces\EntityProviderInterface|string  $provider
     */
    public function register(string $entityClass, $provider): void
    {
        $entityClass = ltrim($entityClass, BACKSLASH);
        $this->entityProviders[$entityClass] = $provider;
    }

    /**
     * @param  array  $classes
     * @param  bool   $filterAbstracts
     */
    public function registerArray(array $classes, bool $filterAbstracts = true): void
    {
        foreach ($classes as $entityClass => $providerClass) {
            if ($filterAbstracts && (new ReflectionClass($providerClass))->isAbstract()) {
                continue;
            }

            $this->register($entityClass, $providerClass);
        }
    }
}

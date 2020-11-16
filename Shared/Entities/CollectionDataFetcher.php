<?php
namespace Shared\Entities;

use Shared\Databases\Interfaces\ServerInterface;
use Shared\Shared;

class CollectionDataFetcher
{
    /**************************
     *   Instance variables   *
     *************************/

    private array $connectionsPerClass = [];

    private ?ServerInterface $db;
    private array $objects;

    private array $perClass = [];
    private array $storagesPerClass = [];


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  array  $objects
     */
    public function __construct(array $objects)
    {
        $this->objects = $objects;
        $this->db      = Shared::db();
    }

    public function go()
    {
        // Group per class
        $this->groupObjectPerClass();

        foreach ($this->perClass as $class => $objectsPerClass) {
            $ids = implode(',', array_keys($objectsPerClass));

            // Fetch the primary data of all entities at once
            $this->fetchPrimaryData($class, $ids, $objectsPerClass);

            // Fetch the connected data of all entities at once
            $this->fetchConnectedData($class, $ids, $objectsPerClass);
        }
    }

    private function fetchConnectedData(string $class, string $ids, array $objectsPerClass): void
    {
        foreach ($this->connectionsPerClass[$class] as $property => $tableName) {
            $dataSets    = $this->db->execute('select * from %q where ID IN (%l)', [$tableName, $ids]);
            $valuesPerId = [];

            foreach ($dataSets as $dataSet) {
                if (!array_key_exists($dataSet['ID'], $valuesPerId)) {
                    $valuesPerId[$dataSet['ID']] = [];
                }

                $valuesPerId[$dataSet['ID']][] = $dataSet[$property];
            }

            foreach ($objectsPerClass as $id => $object) {
                $object->setConnectedData(
                    $property,
                    array_key_exists($id, $valuesPerId) ? $valuesPerId[$id] : []
                );
            }
        }
    }

    private function fetchPrimaryData(string $class, string $ids, array $objectsPerClass)
    {
        foreach ($this->storagesPerClass[$class] as $tableName) {
            $dataSets = $this->db->execute('select * from %q where ID in (%l)', [$tableName, $ids]);

            foreach ($dataSets as $data) {
                $objectsPerClass[$data['ID']]->setData($data, $tableName);
            }
        }
    }

    private function groupObjectPerClass()
    {
        foreach ($this->objects as $object) {
            if (!$object instanceof Interfaces\EntityInterface) {
                continue;
            }

            if ($object->hasData()) {
                // No need to fetch data for objects that already have it
                continue;
            }

            $class = get_class($object);

            if (!array_key_exists($class, $this->perClass)) {
                $this->perClass[$class] = [];
                $provider = $object->getProvider();
                $this->storagesPerClass[$class] = $provider->storages();
                $this->connectionsPerClass[$class] = $provider->getPropertyConnectionTables();
            }

            $this->perClass[$class][$object->id()] = $object;
        }
    }
}

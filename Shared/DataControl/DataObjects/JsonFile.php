<?php
namespace Shared\DataControl\DataObjects;

use Shared\DataControl\DataObject;
use Shared\FileControl\Exceptions\FileNotFoundException;
use Shared\FileControl\File;
use Shared\Json\Json;

class JsonFile extends DataObject
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a data object for the given file path
     *
     * @param  string  $filepath
     *
     * @return  self
     */
    public static function forFilepath(string $filepath): self
    {
        $object = new self();

        $object->setFilepath($filepath);

        return $object;
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int|null
     */
    private $autosaveFrequency = 1;

    /**
     * @var  string
     */
    private $filepath;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Write to file upon destruction
     */
    public function __destruct()
    {
        $this->save();
    }

    public function autosaveFrequency(?int $numberOfChanges): void
    {
        $this->autosaveFrequency = $numberOfChanges;
    }

    /**
     * Changes the filepath where the data object is stored
     *
     * @param  string  $filepath
     *
     * @return  void
     *
     * @throws  FileNotFoundException
     */
    public function setFilepath(string $filepath): void
    {
        if (!File::ensureExistence($filepath)) {
            throw new FileNotFoundException($filepath);
        }

        $this->filepath = $filepath;

        $this->setData(Json::decode(file_get_contents($filepath) ?: '{}') ?: [], false);
    }

    /**
     * Saves the data
     */
    public function save(): void
    {
        File::ensureExistence($this->filepath, Json::encode($this->getData()));
    }

    protected function increaseNumberOfChange()
    {
        parent::increaseNumberOfChange();

        if ($this->autosaveFrequency >= 1 && $this->numberOfChanges % $this->autosaveFrequency === 0) {
            $this->save();
        }
    }
}

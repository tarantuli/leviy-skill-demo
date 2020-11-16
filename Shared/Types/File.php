<?php
namespace Shared\Types;

use Shared\DataControl\DataUri;
use Shared\Databases\MySql\Interfaces\HasMySqlColumnDefinitionInterface;
use Shared\Exceptions\ClassNotInitializedException;
use Shared\FileControl\ContentStore;
use Shared\FileControl\File as FileControlFile;

/**
 * (summary missing)
 */
class File extends AbstractBase implements HasMySqlColumnDefinitionInterface
{
    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  ContentStore
     */
    private static $contentStore;


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  ContentStore  $contentStore
     */
    public static function setContentStore(ContentStore $contentStore)
    {
        self::$contentStore = $contentStore;
    }

    /**
     * @return  ContentStore
     */
    public static function getContentStore()
    {
        return self::$contentStore;
    }


    /************************
     *   Instance methods   *
     ***********************/

    public function getAdditionalParameters(): array
    {
        return [];
    }

    /**
     * Returns the MySQL column definition for this type
     *
     * @param  array  $property
     *
     * @return  string
     */
    public function getMySqlColumnDefinition(array $property): string
    {
        return 'char(40) collate utf8mb4_unicode_ci';
    }

    public function getName(): string
    {
        return 'file';
    }

    public function normalize($value)
    {
        if ($value instanceof FileControlFile) {
            return $value;
        }

        $value = (string) $value;

        if (DataUri::isDataUri($value)) {
            [$value] = DataUri::decode($value);
        }

        return FileControlFile::fromContent($value);
    }

    public function getParamType(): string
    {
        return BACKSLASH . FileControlFile::class;
    }

    /**
     * @param  mixed  $value
     *
     * @return  mixed|string
     *
     * @throws  ClassNotInitializedException
     */
    public function serialize($value): ?string
    {
        if (!self::$contentStore) {
            throw new ClassNotInitializedException(__CLASS__);
        }

        return self::$contentStore->storeContent((string) $value);
    }

    /**
     * @param  mixed  $value
     *
     * @return  FileControlFile|null
     *
     * @throws  ClassNotInitializedException
     */
    public function unserialize(?string $value)
    {
        if (!self::$contentStore) {
            throw new ClassNotInitializedException(__CLASS__);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return FileControlFile::fromContent(self::$contentStore->getContentFromHash($value));
    }

    public function validate($value, array $parameters = []): bool
    {
        return $value instanceof FileControlFile;
    }
}

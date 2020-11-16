<?php
namespace Shared\FileControl;

/**
 * Contains methods to store file contents and retrieve it by hash
 */
class ContentStore
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns the path for the given hash
     *
     * @param  string  $hash
     *
     * @return  string|null
     */
    public static function getPathFromHash(string $hash): ?string
    {
        if (strlen($hash) !== 40) {
            return null;
        }

        return sprintf('%s/%s', substr($hash, 0, 2), substr($hash, 2));
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  Interfaces\FileControllerInterface
     */
    private $fileController;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Initializes the class
     *
     * @param  Interfaces\FileControllerInterface  $fileController
     */
    public function __construct(Interfaces\FileControllerInterface $fileController)
    {
        $this->fileController = $fileController;
    }

    /**
     * Returns the content for the given hash
     *
     * @param  string  $hash
     *
     * @return  string
     */
    public function getContentFromHash(string $hash): string
    {
        $contentPath = self::getPathFromHash($hash);

        return $this->fileController->read($contentPath);
    }

    /**
     * Stores the given content, returns a hash which can be used to retrieve the
     * content later
     *
     * @param  string  $content
     *
     * @return  string
     */
    public function storeContent(string $content): string
    {
        $hash = sha1($content);
        $contentPath = self::getPathFromHash($hash);

        $this->fileController->write($contentPath, $content);

        return $hash;
    }
}

<?php
namespace Project\Tasks;

class FridgeCleaningTask implements Interfaces\TaskInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    public function getDuration(): int
    {
        return 3000;
    }

    public function getDutchDescription(): string
    {
        return 'koelkast schoonmaken';
    }
}

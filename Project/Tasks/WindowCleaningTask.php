<?php
namespace Project\Tasks;

class WindowCleaningTask implements Interfaces\TaskInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    public function getDuration(): int
    {
        return 2100;
    }

    public function getDutchDescription(): string
    {
        return 'ramen lappen';
    }
}

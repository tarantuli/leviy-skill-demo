<?php
namespace Project\Tasks;

class VacuumingTask implements Interfaces\TaskInterface
{
    /************************
     *   Instance methods   *
     ***********************/

    public function getDuration(): int
    {
        return 1260;
    }

    public function getDutchDescription(): string
    {
        return 'stofzuigen';
    }
}

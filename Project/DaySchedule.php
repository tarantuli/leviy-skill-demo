<?php
namespace Project;

use Shared\DateControl\Date;

class DaySchedule
{
    /**************************
     *   Instance variables   *
     *************************/

    private Date $date;

    /**
     * @var  string[]
     */
    private array $taskDescriptions = [];
    private int $taskDuration = 0;


    /************************
     *   Instance methods   *
     ***********************/

    public function __construct(Date $date)
    {
        $this->date = $date;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    public function addTask(Tasks\Interfaces\TaskInterface $task): void
    {
        // Flattened for this demo; a more complex implementation would store the "raw" task parameters
        $this->taskDescriptions[] = $task->getDutchDescription();
        $this->taskDuration += $task->getDuration();
    }

    public function getTaskDescriptions(): array
    {
        return $this->taskDescriptions;
    }

    public function getTotalDuration(): int
    {
        return $this->taskDuration;
    }
}

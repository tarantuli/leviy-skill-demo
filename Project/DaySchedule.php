<?php
namespace Project;

use Shared\DateControl\Date;

class DaySchedule
{
    /**************************
     *   Instance variables   *
     *************************/

    private Date $date;

    private int $taskDuration = 0;

    /**
     * @var  string[]
     */
    private array $tasks = [];


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  Date  $date
     */
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
        $this->tasks[]       = $task->getDutchDescription();
        $this->taskDuration += $task->getDuration();
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function getTotalDuration(): int
    {
        return $this->taskDuration;
    }
}

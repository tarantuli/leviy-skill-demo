<?php
namespace Project;

use Shared\DateControl\Date;
use Shared\DateControl\Duration;

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

    public function addTask(string $description, int $duration): void
    {
        $this->tasks[]       = $description;
        $this->taskDuration += $duration;
    }

    public function toArray(): array
    {
        return [
            'date'     => $this->date,
            'tasks'    => implode(', ', $this->tasks),
            'duration' => (new Duration($this->taskDuration))->toHHMM(),
        ];
    }
}

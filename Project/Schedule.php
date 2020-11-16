<?php
namespace Project;

use Iterator;
use Shared\DateControl\Date;
use Shared\DateControl\DatePeriod;
use Shared\Exceptions\InvalidInputException;

class Schedule implements Iterator
{
    /**************************
     *   Instance variables   *
     *************************/

    private array $dateToIndex;
    private array $daySchedules;
    private Date $from;
    private int $iteratorIndex;
    private DatePeriod $period;
    private Date $till;


    /************************
     *   Instance methods   *
     ***********************/

    public function __construct(Date $from, Date $till)
    {
        $this->from   = $from;
        $this->till   = $till;
        $this->period = new DatePeriod($from, $till);

        $this->initializeDaySchedules();
    }

    public function current(): DaySchedule
    {
        return $this->daySchedules[$this->iteratorIndex];
    }

    public function getDaySchedule(Date $date): DaySchedule
    {
        if (!array_key_exists((string) $date, $this->dateToIndex)) {
            throw new InvalidInputException($date, 'date in this schedule');
        }

        return $this->daySchedules[$this->dateToIndex[(string) $date]];
    }

    public function getFrom(): Date
    {
        return $this->from;
    }

    public function key(): Date
    {
        return $this->current()->getDate();
    }

    public function next(): void
    {
        ++$this->iteratorIndex;
    }

    public function getPeriod(): DatePeriod
    {
        return $this->period;
    }

    public function rewind(): void
    {
        $this->iteratorIndex = 0;
    }

    public function addTask(Date $date, Tasks\Interfaces\TaskInterface $task)
    {
        $this->getDaySchedule($date)->addTask($task);
    }

    public function getTill(): Date
    {
        return $this->till;
    }

    public function valid(): bool
    {
        return array_key_exists($this->iteratorIndex, $this->daySchedules);
    }

    private function initializeDaySchedules(): void
    {
        $this->daySchedules = [];
        $this->dateToIndex  = [];
        $i = 0;

        foreach (new DatePeriod($this->from, $this->till) as $date) {
            $this->daySchedules[$i] = new DaySchedule($date);
            $this->dateToIndex[(string) $date] = $i;

            $i++;
        }
    }
}

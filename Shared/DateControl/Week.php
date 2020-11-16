<?php
namespace Shared\DateControl;

use Iterator;

/**
 * Represents a week
 */
class Week implements Iterator
{
    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a Week instance given by a year and week number composed as "yyyyww"
     *
     * @param  int  $yearweek  A year and week number "yyyyww"
     *
     * @return  self
     */
    public static function fromYearweekNumber(int $yearweek): self
    {
        return new self(floor($yearweek / 100), $yearweek % 100);
    }

    /**
     * Returns a Week instance for today
     *
     * @return  self
     */
    public static function today(): self
    {
        return new self(date(Date::CALENDAR_YEAR), date(Date::WEEK_NUMBER));
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    private $iteratorDay;

    /**
     * @var  int
     */
    private $week;

    /**
     * @var  int
     */
    private $year;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new Week instance
     *
     * @param  int  $year  The calendar year number
     * @param  int  $week  The week number
     */
    public function __construct(int $year, int $week)
    {
        $monday = Date::fromYearAndWeek($year, $week);

        // Determine parts
        $this->year = (int) $monday->getIsoYearNumber();
        $this->week = (int) $monday->getWeekNumber();
    }

    /**
     * Returns a string formatted as "week ww, yyyy"
     *
     * @return  string
     */
    public function __toString(): string
    {
        return sprintf('week %u, %u', $this->week, $this->year);
    }

    /**
     * Iteration method
     *
     * @return  Date
     */
    public function current(): Date
    {
        return Date::fromYearAndWeek($this->year, $this->week, $this->iteratorDay);
    }

    /**
     * Returns the Friday in this week as a Date instance
     *
     * @return  Date
     */
    public function getFriday(): Date
    {
        return $this->getWeekday(Date::FRIDAY);
    }

    /**
     * Returns the ISO year number of this week
     *
     * @return  int
     */
    public function getIsoYearNumber(): int
    {
        return date(Date::ISO_WEEK_YEAR, $this->getMonday()->getTimestamp());
    }

    /**
     * Iteration method
     *
     * @return  int
     */
    public function key(): int
    {
        return $this->iteratorDay;
    }

    /**
     * Returns the Monday in this week as a Date instance
     *
     * @return  Date
     */
    public function getMonday(): Date
    {
        return $this->getWeekday(Date::MONDAY);
    }

    /**
     * Returns the next Week
     *
     * @return  self
     */
    public function getNext(): self
    {
        return $this->getOther(+1);
    }

    /**
     * Iteration method
     *
     * @return  void
     */
    public function next(): void
    {
        ++$this->iteratorDay;
    }

    /**
     * Returns a Week relative to this
     *
     * @param  int  $delta  The offset relative to this, negative numbers refer to the past, positive
     *                      number to the future
     *
     * @return  self
     */
    public function getOther(int $delta): self
    {
        return new self($this->year, $this->week + $delta);
    }

    /**
     * Returns the previous Week
     *
     * @return  self
     */
    public function getPrevious(): self
    {
        return $this->getOther(-1);
    }

    /**
     * Iteration method
     *
     * @return  void
     */
    public function rewind(): void
    {
        $this->iteratorDay = 1;
    }

    /**
     * Returns the Saturday in this week as a Date instance
     *
     * @return  Date
     */
    public function getSaturday(): Date
    {
        return $this->getWeekday(Date::SATURDAY);
    }

    /**
     * Returns the Sunday in this week as a Date instance
     *
     * @return  Date
     */
    public function getSunday(): Date
    {
        return $this->getWeekday(Date::SUNDAY);
    }

    /**
     * Returns the Thursday in this week as a Date instance
     *
     * @return  Date
     */
    public function getThursday(): Date
    {
        return $this->getWeekday(Date::THURSDAY);
    }

    /**
     * Turns this week into an equivalent DatePeriod instance
     *
     * @return  DatePeriod
     */
    public function toDatePeriod(): DatePeriod
    {
        $monday = Date::fromYearAndWeek($this->year, $this->week, 0);

        return new DatePeriod($monday, $monday->getOther(6));
    }

    /**
     * Returns the Tuesday in this week as a Date instance
     *
     * @return  Date
     */
    public function getTuesday(): Date
    {
        return $this->getWeekday(Date::TUESDAY);
    }

    /**
     * Iteration method
     *
     * @return  bool
     */
    public function valid(): bool
    {
        return $this->iteratorDay <= 7;
    }

    /**
     * Returns the Wednesday in this week as a Date instance
     *
     * @return  Date
     */
    public function getWednesday(): Date
    {
        return $this->getWeekday(Date::WEDNESDAY);
    }

    /**
     * Returns a day from this week as a Date instance
     *
     * @param  int  $weekday  A weekday, 1 being Monday
     *
     * @return  Date
     */
    public function getWeekday(int $weekday): Date
    {
        return Date::fromYearAndWeek($this->year, $this->week, $weekday);
    }

    /**
     * Returns the week number of this date
     *
     * @return  int
     */
    public function getWeekNumber(): int
    {
        return (int) $this->week;
    }

    /**
     * Returns the Year instance of this date
     *
     * @return  Year
     */
    public function getYear(): Year
    {
        return new Year($this->year);
    }

    /**
     * Returns the calendar year of this week
     *
     * @return  int
     */
    public function getYearNumber(): int
    {
        return (int) $this->year;
    }

    /**
     * Returns this week as a year and week number composed as "yyyyww". This is not*
     * a valid ISO week date as the year used is not the ISO week-numbering year, but
     * the calendar year
     *
     * @return  int
     */
    public function getYearweekNumber(): int
    {
        return sprintf('%04u%02u', $this->year, $this->week);
    }
}

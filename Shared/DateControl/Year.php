<?php
namespace Shared\DateControl;

use Iterator;
use Shared\Exceptions\InvalidInputException;
use Shared\RestApi\Interfaces\CastableToIntInterface;

/**
 * Represents a year
 */
class Year implements Iterator, CastableToIntInterface
{
    /*****************
     *   Constants   *
     ****************/

    // Interval types
    public const AS_MONTHS = 2;
    public const AS_WEEKS  = 1;

    /**
     * @var  int[]
     */
    private const VALID_INTERVAL_TYPES = [self::AS_WEEKS, self::AS_MONTHS];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a Year instance for today
     *
     * @return  self
     */
    public static function today(): self
    {
        return new self(date(Date::CALENDAR_YEAR));
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    protected $year;

    /**
     * @var  int
     */
    private $intervalType = self::AS_WEEKS;

    /**
     * @var  int
     */
    private $iteratorMonth;

    /**
     * @var  int
     */
    private $iteratorWeek;

    /**
     * @var  int
     */
    private $lastWeek;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new Year instance
     *
     * @param  int  $year  The calendar year
     */
    public function __construct(int $year)
    {
        $this->year = (int) $year;
    }

    /**
     * Returns this year as a string "yyyy"
     *
     * @return  string
     */
    public function __toString(): string
    {
        return (string) $this->year;
    }

    /**
     * Iteration method
     *
     * @return  Month|Week
     */
    public function current()
    {
        switch ($this->intervalType) {
            case self::AS_WEEKS:
                return $this->getWeek($this->iteratorWeek);

            case self::AS_MONTHS:
                return $this->getMonth($this->iteratorMonth);
        }

        return null;
    }

    /**
     * Sets the interval type of this year instance. If it's Year::AS_WEEKS, using
     * foreach will return all the weeks in order; if it's Year::AS_MONTHS, using
     * foreach will return all the months in order
     *
     * @param  int  $type  An interval type
     *
     * @return  bool|null
     *
     * @throws  InvalidInputException
     */
    public function setIntervalType(int $type): ?bool
    {
        if (!in_array($type, self::VALID_INTERVAL_TYPES)) {
            throw new InvalidInputException($type, 'interval type');
        }

        if ($this->intervalType == $type) {
            return null;
        }

        $this->intervalType = $type;

        $this->rewind();

        return true;
    }

    /**
     * Iteration method
     *
     * @return  int
     */
    public function key(): int
    {
        switch ($this->intervalType) {
            case self::AS_WEEKS:
                return $this->iteratorWeek;

            case self::AS_MONTHS:
                return $this->iteratorMonth;
        }

        return false;
    }

    /**
     * Returns a month in this year
     *
     * @param  int  $month  The month number between 1 and 12
     *
     * @return  Month
     */
    public function getMonth(int $month): Month
    {
        return new Month($this->year, $month);
    }

    /**
     * Returns the next Year
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
        switch ($this->intervalType) {
            case self::AS_WEEKS:
                ++$this->iteratorWeek;

                break;

            case self::AS_MONTHS:
                ++$this->iteratorMonth;

                break;
        }
    }

    /**
     * Returns a Year relative to this
     *
     * @param  int  $delta  The offset relative to this, negative numbers refer to the past, positive
     *                      number to the future
     *
     * @return  self
     */
    public function getOther(int $delta): self
    {
        return new self($this->year + $delta);
    }

    /**
     * Returns the previous Year
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
        switch ($this->intervalType) {
            case self::AS_WEEKS:
                $lastDay = new Date($this->year, 12, 28);
                $this->lastWeek     = $lastDay->getWeekNumber();
                $this->iteratorWeek = 1;

                break;

            case self::AS_MONTHS:
                $this->iteratorMonth = 1;

                break;
        }
    }

    /**
     * Turns this year into an equivalent DatePeriod instance
     *
     * @return  DatePeriod
     */
    public function toDatePeriod(): DatePeriod
    {
        return new DatePeriod(new Date($this->year, 1, 1), new Date($this->year, 12, 31));
    }

    public function toRestResponseInt(): ?int
    {
        return $this->year;
    }

    /**
     * Iteration method
     *
     * @return  bool
     */
    public function valid(): bool
    {
        switch ($this->intervalType) {
            case self::AS_WEEKS:
                return $this->iteratorWeek <= $this->lastWeek;

            case self::AS_MONTHS:
                return $this->iteratorMonth <= 12;
        }

        return false;
    }

    /**
     * Returns a week in this year
     *
     * @param  int  $week  The week number in this year
     *
     * @return  Week
     */
    public function getWeek(int $week): Week
    {
        return new Week($this->year, $week);
    }
}

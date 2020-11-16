<?php
namespace Shared\DateControl;

use Iterator;
use Shared\Exceptions\InvalidInputException;

/**
 * Represents a month
 */
class Month implements Iterator
{
    /*****************
     *   Constants   *
     ****************/

    // Interval types
    public const AS_DAYS  = 1;
    public const AS_WEEKS = 2;

    // Month names
    public const APRIL     = 4;
    public const AUGUST    = 8;
    public const DECEMBER  = 12;
    public const FEBRUARY  = 2;
    public const JANUARY   = 1;
    public const JULY      = 7;
    public const JUNE      = 6;
    public const MARCH     = 3;
    public const MAY       = 5;
    public const NOVEMBER  = 11;
    public const OCTOBER   = 10;
    public const SEPTEMBER = 9;

    /**
     * @var  string[]
     */
    protected const MONTH_NAMES = [
        self::JANUARY   => 'January',
        self::FEBRUARY  => 'February',
        self::MARCH     => 'March',
        self::APRIL     => 'April',
        self::MAY       => 'May',
        self::JUNE      => 'June',
        self::JULY      => 'July',
        self::AUGUST    => 'August',
        self::SEPTEMBER => 'September',
        self::OCTOBER   => 'October',
        self::NOVEMBER  => 'November',
        self::DECEMBER  => 'December',
    ];

    /**
     * @var  int[]
     */
    private const VALID_INTERVAL_TYPES = [self::AS_DAYS, self::AS_WEEKS];


    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  string[]
     */
    private static $DUTCH_MONTH_NAMES = [
        self::JANUARY   => 'januari',
        self::FEBRUARY  => 'februari',
        self::MARCH     => 'maart',
        self::APRIL     => 'april',
        self::MAY       => 'mei',
        self::JUNE      => 'juni',
        self::JULY      => 'juli',
        self::AUGUST    => 'augustus',
        self::SEPTEMBER => 'september',
        self::OCTOBER   => 'oktober',
        self::NOVEMBER  => 'november',
        self::DECEMBER  => 'december',
    ];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a Month instance given by a year and month number composed as "yyyymm"
     *
     * @param  int  $yearmonth  A year and month number "yyyymm"
     *
     * @return  self
     */
    public static function fromYearmonthNumber(int $yearmonth): self
    {
        return new self(floor($yearmonth / 100), $yearmonth % 100);
    }

    /**
     * Returns a Month instance for today
     *
     * @return  self
     */
    public static function today(): self
    {
        return new self(date(Date::CALENDAR_YEAR), date(Date::MONTH_NUMBER));
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    private $intervalType = self::AS_DAYS;

    /**
     * @var  int
     */
    private $iteratorDay;

    /**
     * @var  int
     */
    private $iteratorWeek;

    /**
     * @var  int
     */
    private $lastDay;

    /**
     * @var  int
     */
    private $month;

    /**
     * @var  int
     */
    private $numberOfWeeks;

    /**
     * @var  int
     */
    private $year;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new Month instance
     *
     * @param  int  $year   The calendar year
     * @param  int  $month  The month number
     */
    public function __construct(int $year, int $month)
    {
        $lastDay = new Date($year, $month + 1, 0);

        // Determine parts
        $this->year    = $lastDay->getYearNumber();
        $this->month   = $lastDay->getMonthNumber();
        $this->lastDay = $lastDay->getDayNumber();
    }

    /**
     * Returns this month name and year
     *
     * @return  string
     */
    public function __toString(): string
    {
        return $this->getName() . ' ' . $this->getYearNumber();
    }

    /**
     * Iteration method
     *
     * @return  Date|Week
     */
    public function current()
    {
        switch ($this->intervalType) {
            case self::AS_DAYS:
                return $this->getDay($this->iteratorDay);

            case self::AS_WEEKS:
                return $this->getWeek($this->iteratorWeek);
        }

        return null;
    }

    /**
     * Returns a day in this month
     *
     * @param  int  $dayInMonth  One being the first day of the month
     *
     * @return  Date
     */
    public function getDay(int $dayInMonth): Date
    {
        return new Date($this->year, $this->month, $dayInMonth);
    }

    /**
     * Returns the Dutch name of this month
     *
     * @return  string
     */
    public function getDutchName(): string
    {
        return self::$DUTCH_MONTH_NAMES[$this->month];
    }

    /**
     * Returns the first day in this month
     *
     * @return  Date
     */
    public function getFirstDay(): Date
    {
        return new Date($this->year, $this->month, 1);
    }

    /**
     * Sets the interval type of this month instance. If it's Month::AS_DAYS, using
     * foreach will return all the days in order; if it's Month::AS_WEEKS, using
     * foreach will return all the weeks in order
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
     * @return  int|null
     */
    public function key(): ?int
    {
        switch ($this->intervalType) {
            case self::AS_DAYS:
                return $this->iteratorDay;

            case self::AS_WEEKS:
                return $this->iteratorWeek;
        }

        return null;
    }

    /**
     * Returns the last day in this month
     *
     * @return  Date
     */
    public function getLastDay(): Date
    {
        return new Date($this->year, $this->month + 1, 0);
    }

    /**
     * Returns the month number
     *
     * @return  int
     */
    public function getMonthNumber(): int
    {
        return $this->month;
    }

    /**
     * Returns the name of this month
     *
     * @return  string
     */
    public function getName(): string
    {
        return static::MONTH_NAMES[$this->month];
    }

    /**
     * Returns the next Month
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
            case self::AS_DAYS:
                ++$this->iteratorDay;

                break;

            case self::AS_WEEKS:
                ++$this->iteratorWeek;

                break;
        }
    }

    /**
     * @return  int
     */
    public function getNumberOfWeeks(): int
    {
        if ($this->numberOfWeeks === null) {
            $day1      = new Date($this->year, $this->month, 1);
            $dayZ      = new Date($this->year, $this->month + 1, 0);
            $firstWeek = $day1->getWeekNumber();
            $lastWeek  = $dayZ->getWeekNumber();

            if ($firstWeek > $lastWeek) {
                if ($this->month == 12) {
                    $dayZ     = new Date($this->year, $this->month + 1, -7);
                    $lastWeek = $dayZ->getWeekNumber();
                }
                else {
                    $firstWeek = 0;
                }
            }

            $this->numberOfWeeks = $lastWeek - $firstWeek + 1;
        }

        return $this->numberOfWeeks;
    }

    /**
     * Returns a Month relative to this
     *
     * @param  int  $delta  The offset relative to this, negative numbers refer to the past, positive
     *                      number to the future
     *
     * @return  self
     */
    public function getOther(int $delta): self
    {
        return new self($this->year, $this->month + $delta);
    }

    /**
     * Returns the previous Month
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
            case self::AS_DAYS:
                $this->iteratorDay = 1;

                break;

            case self::AS_WEEKS:
                $this->getNumberOfWeeks();
                $this->iteratorWeek = 1;

                break;
        }
    }

    /**
     * Turns this month into an equivalent DatePeriod instance
     *
     * @return  DatePeriod
     */
    public function toDatePeriod(): DatePeriod
    {
        $firstDay = new Date($this->year, $this->month, 1);

        return new DatePeriod($firstDay, $firstDay->getOther($this->lastDay - 1));
    }

    /**
     * Iteration method
     *
     * @return  bool
     */
    public function valid(): bool
    {
        switch ($this->intervalType) {
            case self::AS_DAYS:
                return $this->iteratorDay <= $this->lastDay;

            case self::AS_WEEKS:
                return $this->iteratorWeek <= $this->numberOfWeeks;
        }

        return false;
    }

    public function getValue(): int
    {
        return $this->month;
    }

    /**
     * Returns a week in this month
     *
     * @param  int  $weekInMonth  One being the first week in this month (the week of the first day of the
     *                            month)
     *
     * @return  Week
     */
    public function getWeek(int $weekInMonth): Week
    {
        $day1  = new Date($this->year, $this->month, 1);
        $week1 = $day1->getWeek();

        return $week1->getOther($weekInMonth - 1);
    }

    /**
     * Returns the Year instance of this month
     *
     * @return  Year
     */
    public function getYear(): Year
    {
        return new Year($this->year);
    }

    /**
     * Returns this month as a year and month number composed as "yyyymm"
     *
     * @return  int
     */
    public function getYearmonthNumber(): int
    {
        return sprintf('%04u%02u', $this->year, $this->month);
    }

    /**
     * Returns the calendar year of this month
     *
     * @return  int
     */
    public function getYearNumber(): int
    {
        return $this->year;
    }
}

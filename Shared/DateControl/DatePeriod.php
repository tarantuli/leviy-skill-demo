<?php
namespace Shared\DateControl;

use Iterator;
use Shared\Exceptions\InvalidInputException;

/**
 * Represents a range of dates
 */
class DatePeriod implements Iterator
{
    /*****************
     *   Constants   *
     ****************/

    // Interval types
    public const AS_DAYS   = 1;
    public const AS_HOURS  = 2;
    public const AS_MONTHS = 3;

    /**
     * @var  int[]
     */
    private const VALID_INTERVAL_TYPES = [self::AS_DAYS, self::AS_HOURS, self::AS_MONTHS];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string  $value
     *
     * @return  DatePeriod
     */
    public static function fromCsvs(string $value): DatePeriod
    {
        [$from, $to] = explode(',', $value);

        return new self($from, $to);
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  string
     */
    private $endCondition;

    /**
     * @var  Date
     */
    private $from;

    /**
     * @var  int
     */
    private $intervalType = self::AS_DAYS;

    /**
     * @var  Date
     */
    private $iteratorDay;

    /**
     * @var  DateTime
     */
    private $iteratorHour;

    /**
     * @var  int
     */
    private $iteratorHourCounter;

    /**
     * @var  Month
     */
    private $iteratorMonth;

    /**
     * @var  Date
     */
    private $to;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new DatePeriod instance
     *
     * @param  Date  $from  The first date of the period
     * @param  Date  $to    The last date of the period
     */
    public function __construct(Date $from, Date $to)
    {
        if (!$from) {
            $from = Date::today();
        }

        if (!$to) {
            $to = Date::today();
        }

        if (is_string($from)) {
            $from = Date::fromString($from);
        }

        if (is_string($to)) {
            $to = Date::fromString($to);
        }

        if ((string) $from > (string) $to) {
            // Swap values
            $temp = $from;
            $from = $to;
            $to   = $temp;

            unset($temp);
        }

        $this->from = $from;
        $this->to   = $to;
    }

    public function contains(Date $date): bool
    {
        return $date->getTimestamp() >= $this->from->getTimestamp()
            && $date->getTimestamp() <= $this->to->getTimestamp();
    }

    /**
     * Iteration method
     *
     * @return  mixed
     */
    public function current()
    {
        switch ($this->intervalType) {
            case self::AS_DAYS:
                return $this->iteratorDay;

            case self::AS_HOURS:
                return $this->iteratorHour;

            case self::AS_MONTHS:
                return $this->iteratorMonth;
        }

        return false;
    }

    public function getFrom(): Date
    {
        return $this->from;
    }

    /**
     * Sets the interval type of this period instance. If it's DatePeriod::AS_DAYS,
     * using foreach will return all the days in order; if it's DatePeriod::AS_HOURS,
     * using foreach will return all the hours in order; if it's
     * DatePeriod::AS_MONTHS, it will return the months
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
                return $this->iteratorDay->getTimestamp();

            case self::AS_HOURS:
                return $this->iteratorHourCounter;

            case self::AS_MONTHS:
                return $this->iteratorMonth->getYearmonthNumber();
        }

        return null;
    }

    public function getLength(): int
    {
        return 1 + ($this->to->getTimestamp() - $this->from->getTimestamp()) / ONE_DAY;
    }

    /**
     * Iteration method
     */
    public function next()
    {
        switch ($this->intervalType) {
            case self::AS_DAYS:
                $this->iteratorDay = $this->iteratorDay->getNext();

                break;

            case self::AS_HOURS:
                $this->iteratorHour->addHours(1);

                ++$this->iteratorHourCounter;

                break;

            case self::AS_MONTHS:
                $this->iteratorMonth = $this->iteratorMonth->getNext();

                break;
        }
    }

    /**
     * Iteration method
     */
    public function rewind()
    {
        switch ($this->intervalType) {
            case self::AS_DAYS:
                $this->iteratorDay  = $this->from;
                $this->endCondition = (string) $this->to;

                break;

            case self::AS_HOURS:
                $this->iteratorHourCounter = 0;
                $this->iteratorHour = $this->from->toDateTime();
                $this->endCondition = (string) $this->to->toDateTime(23, 59, 59);

                break;

            case self::AS_MONTHS:
                $this->iteratorMonth = $this->from->getMonth();
                $this->endCondition  = $this->to->getMonth()->getYearmonthNumber();
        }
    }

    public function getTo(): Date
    {
        return $this->to;
    }

    /**
     * Iteration method
     */
    public function valid()
    {
        switch ($this->intervalType) {
            case self::AS_DAYS:
                return (string) $this->iteratorDay <= $this->endCondition;

            case self::AS_HOURS:
                return (string) $this->iteratorHour < $this->endCondition;

            case self::AS_MONTHS:
                return $this->iteratorMonth->getYearmonthNumber() <= $this->endCondition;
        }

        return false;
    }
}

<?php
namespace Shared\DateControl;

use Iterator;
use Shared\Exceptions\InvalidInputException;

/**
 * Represents a date and time
 */
class DateTime implements Iterator
{
    /*****************
     *   Constants   *
     ****************/

    // Interval types
    public const AS_HOURS = 1;

    /**
     * @var  int[]
     */
    private const VALID_INTERVAL_TYPES = [self::AS_HOURS];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Returns a DateTime instance for the given string
     *
     * @param  string  $string  A string denoting a date and/or time
     *
     * @return  self
     */
    public static function fromString(string $string): self
    {
        if (preg_match('#(\d+-\d+-\d+T\d+:\d+:\d+(?:\.\d+)?)([A-Z])#', $string, $match)) {
            $offset = ord($match[2]) - 64;

            if ($offset >= 11) {
                $offset -= 1;
            }

            if ($offset >= 13) {
                $offset = 12 - $offset;
            }

            if ($offset == -13) {
                $offset = 0;
            }

            $string  = $match[1];
            $string .= sprintf('%+2d:00', $offset);
        }

        return self::fromTimestamp(strtotime($string));
    }

    /**
     * Returns a DateTime instance for the given timestamp
     *
     * @param  int  $timestamp  A timestamp integer
     *
     * @return  DateTime
     *
     * @throws  InvalidInputException
     */
    public static function fromTimestamp(int $timestamp): DateTime
    {
        if (!$timestamp) {
            throw new InvalidInputException($timestamp, 'timestamp');
        }

        return new self(
            date(Date::CALENDAR_YEAR, $timestamp),
            date(Date::MONTH_NUMBER, $timestamp),
            date(Date::DAY_OF_MONTH, $timestamp),
            date(Time::HOUR, $timestamp),
            date(Time::MINUTE, $timestamp),
            date(Time::SECOND, $timestamp)
        );
    }

    /**
     * Returns a DateTime instance for now
     *
     * @return  self
     */
    public static function now(): self
    {
        return new self(
            date(Date::CALENDAR_YEAR),
            date(Date::MONTH_NUMBER),
            date(Date::DAY_OF_MONTH),
            date(Time::HOUR),
            date(Time::MINUTE),
            date(Time::SECOND)
        );
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    private $day;

    /**
     * @var  int
     */
    private $hour;

    /**
     * @var  int
     */
    private $intervalType = self::AS_HOURS;

    /**
     * @var  int
     */
    private $iteratorHour;

    /**
     * @var  int
     */
    private $minute;

    /**
     * @var  int
     */
    private $month;

    /**
     * @var  int
     */
    private $second;

    /**
     * @var  int
     */
    private $timestamp;

    /**
     * @var  int
     */
    private $year;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new Date instance
     *
     * @param  int  $year    The year number
     * @param  int  $month   The month number
     * @param  int  $day     The day of the month
     * @param  int  $hour
     * @param  int  $minute
     * @param  int  $second
     */
    public function __construct(int $year, int $month, int $day, int $hour, int $minute, int $second)
    {
        $this->year   = $year;
        $this->month  = $month;
        $this->day    = $day;
        $this->hour   = $hour;
        $this->minute = $minute;
        $this->second = $second;

        $this->recalibrate();
    }

    /**
     * Returns this date and time as a "yyyy-mm-dd hh:mm:ss" string
     *
     * @return  string
     */
    public function __toString(): string
    {
        return date(DATETIME, $this->timestamp);
    }

    /**
     * Iteration method
     *
     * @return  int
     */
    public function current(): int
    {
        switch ($this->intervalType) {
            case self::AS_HOURS:
                return $this->iteratorHour;
        }

        return false;
    }

    /**
     * Returns this date formatted according to the given format string using
     * strftime()
     *
     * @param  string  $format  The strftime() formatting string to use
     *
     * @return  string
     */
    public function format(string $format): string
    {
        return strftime($format, $this->timestamp);
    }

    /**
     * Adds hours to this instance
     *
     * @param  int  $delta  The hours to add; if it's negative, the hours are subtracted
     *
     * @return  self
     */
    public function addHours(int $delta): self
    {
        $this->hour += $delta;

        $this->recalibrate();

        return $this;
    }

    /**
     * Sets the interval type of this date instance. If it's self::AS_HOURS, using
     * foreach will return all the hours in order
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
            case self::AS_HOURS:
                return $this->iteratorHour;
        }

        return false;
    }

    /**
     * Iteration method
     *
     * @return  void
     */
    public function next(): void
    {
        switch ($this->intervalType) {
            case self::AS_HOURS:
                ++$this->iteratorHour;

                break;
        }
    }

    /**
     * Iteration method
     *
     * @return  void
     */
    public function rewind(): void
    {
        switch ($this->intervalType) {
            case self::AS_HOURS:
                $this->iteratorHour = 0;

                break;
        }
    }

    /**
     * Subtracts hours to this instance
     *
     * @param  int  $delta  The hours to subtract; if it's negative, the hours are added
     *
     * @return  self
     */
    public function subtractHours(int $delta): self
    {
        return $this->addHours(-$delta);
    }

    /**
     * @param  Time  $time
     */
    public function setTime(Time $time): void
    {
        $this->hour   = $time->getHour();
        $this->minute = $time->getMinute();
        $this->second = $time->getSecond();
    }

    /**
     * Iteration method
     *
     * @return  bool
     */
    public function valid(): bool
    {
        switch ($this->intervalType) {
            case self::AS_HOURS:
                return $this->iteratorHour <= 23;
        }

        return false;
    }

    /**
     * Recalibrates the parts of this instance after setting or adjusting one or more
     * parts
     *
     * @return  void
     */
    private function recalibrate(): void
    {
        $this->timestamp = mktime(
            $this->hour,
            $this->minute,
            $this->second,
            $this->month,
            $this->day,
            $this->year
        );

        // Determine parts
        $this->year   = (int) date(Date::CALENDAR_YEAR, $this->timestamp);
        $this->month  = (int) date(Date::MONTH_NUMBER,  $this->timestamp);
        $this->day    = (int) date(Date::DAY_OF_MONTH,  $this->timestamp);
        $this->hour   = (int) date(Time::HOUR,          $this->timestamp);
        $this->minute = (int) date(Time::MINUTE,        $this->timestamp);
        $this->second = (int) date(Time::SECOND,        $this->timestamp);
    }
}

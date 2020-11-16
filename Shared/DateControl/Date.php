<?php
namespace Shared\DateControl;

use DateTime as InternalDateTime;
use Iterator;
use Shared\DataControl\Variable;
use Shared\Exceptions\InvalidInputException;

/**
 * Represents a date
 */
class Date implements Iterator
{
    /*****************
     *   Constants   *
     ****************/

    // Interval types
    public const AS_HOURS = 1;

    // date() format codes
    public const CALENDAR_YEAR = 'Y';
    public const DAY_OF_MONTH  = 'd';
    public const MONTH_NUMBER  = 'm';

    // Week numbers
    public const ISO_WEEK_YEAR = 'o';
    public const WEEK_NUMBER   = 'W';

    // Weekday, Monday = 1, Sunday = 7
    public const DAY_OF_WEEK = 'N';

    // Weekday names
    public const FRIDAY    = 5;
    public const MONDAY    = 1;
    public const SATURDAY  = 6;
    public const SUNDAY    = 7;
    public const THURSDAY  = 4;
    public const TUESDAY   = 2;
    public const WEDNESDAY = 3;

    /**
     * @var  string[]
     */
    protected const WEEKDAY_NAMES = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    /**
     * @var  int[]
     */
    private const VALID_INTERVAL_TYPES = [self::AS_HOURS];


    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  string[]
     */
    private static $DUTCH_WEEKDAY_NAMES = [
        1 => 'maandag',
        2 => 'dinsdag',
        3 => 'woensdag',
        4 => 'donderdag',
        5 => 'vrijdag',
        6 => 'zaterdag',
        7 => 'zondag',
    ];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  mixed  $value
     *
     * @return  void
     *
     * @throws  InvalidInputException
     */
    public static function checkIsInt($value): void
    {
        if (!Variable::isIntval($value)) {
            throw new InvalidInputException($value, 'integer');
        }
    }

    /**
     * Helper function that sets the locale to Dutch for date and time functions
     *
     * @return  void
     */
    public static function setDutchLocale(): void
    {
        setlocale(LC_TIME, ['nl_NL.utf8', 'nl_NL', 'Dutch']);
    }

    /**
     * Returns the Dutch name of the given weekday
     *
     * @param  int  $weekdayNumber  The weekday, one being Monday
     *
     * @return  string|null
     */
    public static function dutchWeekdayName(int $weekdayNumber): ?string
    {
        return array_key_exists($weekdayNumber, self::$DUTCH_WEEKDAY_NAMES)
            ? self::$DUTCH_WEEKDAY_NAMES[$weekdayNumber]
            : null;
    }

    /**
     * Returns a Date instance for the date given by the string as determined by
     * strtotime()
     *
     * @param  string  $string  The date string
     *
     * @return  Date
     *
     * @throws  InvalidInputException
     */
    public static function fromString(string $string): Date
    {
        if (preg_match('#(\d+) (\d+) (\d{4})#', $string, $match)) {
            return new static($match[3], $match[2], $match[1]);
        }

        $timestamp = strtotime($string);

        if (!Variable::isIntval($timestamp)) {
            throw new InvalidInputException($string, 'date string');
        }

        return static::fromTimestamp($timestamp);
    }

    /**
     * Returns a Date instance for the given timestamp
     *
     * @param  int  $timestamp
     *
     * @return  static
     */
    public static function fromTimestamp(int $timestamp)
    {
        return new static(
            date(self::CALENDAR_YEAR, $timestamp),
            date(self::MONTH_NUMBER, $timestamp),
            date(self::DAY_OF_MONTH, $timestamp)
        );
    }

    /**
     * Returns a Date instance for the given year, week and optionally weekday
     *
     * @param  int  $year  The calendar year number
     * @param  int  $week  The week number
     * @param  int  $day   The weekday, one being Monday
     *
     * @return  static
     */
    public static function fromYearAndWeek(int $year, int $week, int $day = 1)
    {
        self::checkIsInt($year);
        self::checkIsInt($week);
        self::checkIsInt($day);

        $feb1       = mktime(0, 0, 0, 2, 1, $year);
        $feb1Week   = date(self::WEEK_NUMBER, $feb1);
        $febWeekday = date(self::DAY_OF_WEEK, $feb1) - 1;
        $timestamp  = mktime(0, 0, 0, 2, 1 - $febWeekday + 7 * ($week - $feb1Week) + ($day - 1), $year);

        return static::fromTimestamp($timestamp);
    }

    /**
     * Returns a Date instance relative to today. For all arguments, negative numbers
     * refer to the past, positive numbers to the future
     *
     * @param  int  $daysDelta    The number of days relative to today
     * @param  int  $monthsDelta  The number of months relative to today
     * @param  int  $yearsDelta   The number of years relative to today
     *
     * @return  static
     */
    public static function relativeToToday(int $daysDelta, int $monthsDelta = 0, int $yearsDelta = 0)
    {
        return new static(
            date(self::CALENDAR_YEAR) + $yearsDelta,
            date(self::MONTH_NUMBER) + $monthsDelta,
            date(self::DAY_OF_MONTH) + $daysDelta
        );
    }

    /**
     * Returns a Date instance for today
     *
     * @return  static
     */
    public static function today()
    {
        return new static(date(self::CALENDAR_YEAR), date(self::MONTH_NUMBER), date(self::DAY_OF_MONTH));
    }

    /**
     * Returns a Date instance for tomorrow
     *
     * @return  static
     */
    public static function tomorrow()
    {
        return new static(date(self::CALENDAR_YEAR), date(self::MONTH_NUMBER), date(self::DAY_OF_MONTH) + 1);
    }

    /**
     * Returns the name of the given weekday
     *
     * @param  int  $weekdayNumber  The weekday, one being Monday
     *
     * @return  string|null
     */
    public static function weekdayName(int $weekdayNumber): ?string
    {
        self::checkIsInt($weekdayNumber);

        return array_key_exists($weekdayNumber, static::WEEKDAY_NAMES)
            ? static::WEEKDAY_NAMES[$weekdayNumber]
            : null;
    }

    /**
     * Returns a Date instance for yesterday
     *
     * @return  static
     */
    public static function yesterday()
    {
        return new static(date(self::CALENDAR_YEAR), date(self::MONTH_NUMBER), date(self::DAY_OF_MONTH) - 1);
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    protected $day;

    /**
     * @var  int
     */
    protected $intervalType = self::AS_HOURS;

    /**
     * @var  int
     */
    protected $iteratorHour;

    /**
     * @var  int
     */
    protected $month;

    /**
     * @var  int
     */
    protected $timestamp;

    /**
     * @var  int
     */
    protected $year;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * Creates a new Date instance
     *
     * @param  int|string  $year   The year number
     * @param  int|string  $month  The month number
     * @param  int|string  $day    The day of the month
     */
    public function __construct($year, $month, $day)
    {
        self::checkIsInt($year);
        self::checkIsInt($month);
        self::checkIsInt($day);

        $this->year  = (int) $year;
        $this->month = (int) $month;
        $this->day   = (int) $day;

        $this->recalibrate();
    }

    /**
     * Returns this date as a "yyyy-mm-dd" string
     *
     * @return  string
     */
    public function __toString(): string
    {
        return date('Y-m-d', $this->timestamp);
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
     * Returns the day of the month of this date
     *
     * @return  int
     */
    public function getDayNumber(): int
    {
        return (int) $this->day;
    }

    /**
     * Returns the Dutch name of this weekday
     *
     * @return  string
     */
    public function getDutchWeekdayName(): string
    {
        $weekdayNumber = $this->getWeekdayNumber();

        return self::dutchWeekdayName($weekdayNumber);
    }

    /**
     * Returns a DateTime object for the last second of this date
     *
     * @return  DateTime
     */
    public function getEndOfDay(): DateTime
    {
        return new DateTime($this->year, $this->month, $this->day, 23, 59, 59);
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
        self::checkIsInt($type);

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

    public function getIsoYearNumber()
    {
        return date(self::ISO_WEEK_YEAR, $this->timestamp);
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
     * Returns the Month instance of this date
     *
     * @return  Month
     */
    public function getMonth(): Month
    {
        return new Month($this->year, $this->month);
    }

    /**
     * Returns the month number of this date
     *
     * @return  int
     */
    public function getMonthNumber(): int
    {
        return $this->month;
    }

    /**
     * Returns the next Date
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
            case self::AS_HOURS:
                ++$this->iteratorHour;

                break;
        }
    }

    /**
     * Returns a Date relative to this
     *
     * @param  int  $delta  The offset relative to this, negative numbers refer to the past, positive
     *                      number to the future
     *
     * @return  self
     */
    public function getOther(int $delta): self
    {
        return new self($this->year, $this->month, $this->day + $delta);
    }

    /**
     * Returns the previous Date
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
            case self::AS_HOURS:
                $this->iteratorHour = 0;

                break;
        }
    }

    /**
     * Returns a DateTime object for the first second of this date
     *
     * @return  DateTime
     */
    public function getStartOfDay(): DateTime
    {
        return new DateTime($this->year, $this->month, $this->day, 0, 0, 0);
    }

    /**
     * Returns the timestamp of the first second of this date
     *
     * @return  int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Creates a new DateTime instance with the date values of this instance and the
     * given time values
     *
     * @param  int  $hour
     * @param  int  $minute
     * @param  int  $seconds
     *
     * @return  DateTime
     */
    public function toDateTime(int $hour = 0, int $minute = 0, int $seconds = 0): DateTime
    {
        return new DateTime($this->year, $this->month, $this->day, $hour, $minute, $seconds);
    }

    public function toDdMmmYyyyString()
    {
        return strftime('%#d %B %Y', $this->timestamp);
    }

    /**
     * Returns this date as a descriptive string in Dutch relative to today, e.g.
     * "vandaag", "gisteren", "afgelopen zondag", "18 januari"
     *
     * @return  string
     */
    public function toDescriptiveDutchString(): string
    {
        $today   = new InternalDateTime(self::today());
        $thisDay = new InternalDateTime((string) $this);
        $diff    = $thisDay->diff($today);

        if ($diff->days == 0) {
            return 'vandaag';
        }

        if (!$diff->invert) {
            if ($diff->days == 1) {
                return 'gisteren';
            }

            if ($diff->days == 2) {
                return 'eergisteren';
            }

            if ($diff->days < 7) {
                return 'afgelopen ' . $this->getWeekdayName();
            }
        }
        else {
            if ($diff->days == 1) {
                return 'morgen';
            }

            if ($diff->days == 2) {
                return 'overmorgen';
            }

            if ($diff->days < 7) {
                return $this->getDutchWeekdayName();
            }
        }

        return $this->getDayNumber() . ' ' . $this->getMonth()->getDutchName();
    }

    /**
     * Returns this date as a descriptive string relative to today, e.g. "today",
     * "yesterday", "last Sunday", "January 18"
     *
     * @return  string
     */
    public function toDescriptiveString(): string
    {
        $today   = new InternalDateTime(self::today());
        $thisDay = new InternalDateTime((string) $this);
        $diff    = $thisDay->diff($today);

        if ($diff->days == 0) {
            return 'today';
        }

        if (!$diff->invert) {
            if ($diff->days == 1) {
                return 'yesterday';
            }

            if ($diff->days == 2) {
                return 'day before yesterday';
            }

            if ($diff->days < 7) {
                return 'last ' . $this->getWeekdayName();
            }
        }
        else {
            if ($diff->days == 1) {
                return 'tomorrow';
            }

            if ($diff->days == 2) {
                return 'day after tomorrow';
            }

            if ($diff->days < 7) {
                return $this->getWeekdayName();
            }
        }

        if ($diff->days < 365) {
            return $this->getMonth()->getName() . ' ' . $this->getDayNumber();
        }

        return $this->getMonth()->getName() . ' ' . $this->getDayNumber() . ', ' . $this->getYearNumber();
    }

    /**
     * Returns this date as a "dd-mm-yyyy" string
     *
     * @return  string
     */
    public function toDmyString(): string
    {
        return date('d-m-Y', $this->timestamp);
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
     * Returns the Week instance of this date
     *
     * @return  Week
     */
    public function getWeek(): Week
    {
        return new Week(
            date(self::ISO_WEEK_YEAR, $this->timestamp),
            date(self::WEEK_NUMBER, $this->timestamp)
        );
    }

    /**
     * Returns the weekday name of this date
     *
     * @return  string
     */
    public function getWeekdayName(): string
    {
        $weekdayNumber = $this->getWeekdayNumber();

        return static::weekdayName($weekdayNumber);
    }

    /**
     * Returns the weekday of this date
     *
     * @return  int
     */
    public function getWeekdayNumber(): int
    {
        return date(self::DAY_OF_WEEK, $this->timestamp);
    }

    /**
     * Returns whether this date is a weekend day (a Saturday or a Sunday)
     *
     * @return  bool
     */
    public function isWeekendDay(): bool
    {
        return in_array($this->getWeekdayNumber(), [self::SATURDAY, self::SUNDAY]);
    }

    /**
     * Returns the week number of this date
     *
     * @return  int
     */
    public function getWeekNumber(): int
    {
        return date(self::WEEK_NUMBER, $this->timestamp);
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
     * Returns the year number of this date
     *
     * @return  int
     */
    public function getYearNumber(): int
    {
        return $this->year;
    }

    /**
     * Recalibrates the parts of this instance after setting or adjusting one or more
     * parts
     *
     * @return  void
     */
    private function recalibrate(): void
    {
        $this->timestamp = mktime(0, 0, 0, $this->month, $this->day, $this->year);

        // Determine parts
        $this->year  = (int) date(self::CALENDAR_YEAR, $this->timestamp);
        $this->month = (int) date(self::MONTH_NUMBER,  $this->timestamp);
        $this->day   = (int) date(self::DAY_OF_MONTH,  $this->timestamp);
    }
}

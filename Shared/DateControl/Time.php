<?php
namespace Shared\DateControl;

use Shared\DataControl\Regex;

/**
 * Represents a time
 */
class Time
{
    /*****************
     *   Constants   *
     ****************/

    // Time format codes
    public const HOUR   = 'H';
    public const MINUTE = 'i';
    public const SECOND = 's';


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  string  $string
     *
     * @return  Time|null
     */
    public static function fromString(string $string): ?Time
    {
        if ($parts = Regex::getMatch('^(\d+):(\d+)$', $string)) {
            return new self(3600 * $parts[1] + 60 * $parts[2]);
        }

        return null;
    }

    public static function now()
    {
        return new self(3600 * date('H') + 60 * date('i'));
    }

    /**
     * Converts the given time in seconds into m:ss format
     *
     * @param  int  $time
     *
     * @return  string
     */
    public static function toMSS(int $time): string
    {
        $minutes = floor($time / 60);
        $seconds = $time - 60 * $minutes;

        return sprintf('%u:%02s', $minutes, $seconds);
    }


    /**************************
     *   Instance variables   *
     *************************/

    /**
     * @var  int
     */
    private $seconds;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  int  $seconds
     */
    public function __construct(int $seconds = 0)
    {
        $this->setTotalSeconds($seconds);
    }

    /**
     * @return  string
     */
    public function __toString(): string
    {
        return sprintf('%02u:%02u', $this->getHour(), $this->getMinute());
    }

    /**
     * @return  int
     */
    public function getHour(): int
    {
        return (int) floor($this->seconds / 3600);
    }

    /**
     * @return  int
     */
    public function getMinute(): int
    {
        return (int) floor(($this->seconds - 3600 * $this->getHour()) / 60);
    }

    /**
     * @return  int
     */
    public function getSecond(): int
    {
        return $this->seconds - 60 * $this->getMinute() - 3600 * $this->getHour();
    }

    /**
     * @param  int  $seconds
     */
    public function setTotalSeconds(int $seconds)
    {
        $this->seconds = $seconds;
    }

    /**
     * @return  int
     */
    public function getTotalSeconds(): int
    {
        return $this->seconds;
    }
}

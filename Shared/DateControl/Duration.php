<?php
namespace Shared\DateControl;

class Duration
{
    /**************************
     *   Instance variables   *
     *************************/

    /**
     * In seconds
     *
     * @var  int
     */
    private int $duration;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  int  $duration  In seconds
     */
    public function __construct(int $duration)
    {
        $this->duration = $duration;
    }

    public function toHHMM(): string
    {
        $hours   = floor($this->duration / 3600);
        $minutes = floor(($this->duration - 3600 * $hours) / 60);

        return sprintf('%02u:%02u', $hours, $minutes);
    }
}

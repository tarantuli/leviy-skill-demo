<?php
namespace Project;

use Shared\DateControl\Date;

class DaySchedule
{
    /**************************
     *   Instance variables   *
     *************************/

    private Date $date;


    /************************
     *   Instance methods   *
     ***********************/

    public function __construct(Date $date)
    {
        $this->date = $date;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    public function toArray(): array
    {
    }
}

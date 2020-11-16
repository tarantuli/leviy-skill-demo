<?php
namespace Project;

use Shared\DateControl\Date;
use Shared\Providers\AbstractSingletonProvider;

class ScheduleProvider extends AbstractSingletonProvider
{
    /************************
     *   Instance methods   *
     ***********************/

    public function generate(Date $from, Date $till): Schedule
    {
        $schedule = new Schedule($from, $till);

        // If the results aren't cached or stored on a day-by-day basis, it's more efficient to determine tasks
        // per week and per month. Otherwise, tasks should be determined for each date
        return $schedule;
    }
}

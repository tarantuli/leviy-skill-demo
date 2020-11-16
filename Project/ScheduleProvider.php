<?php
namespace Project;

use Shared\DateControl\Date;
use Shared\Providers\AbstractSingletonProvider;

class ScheduleProvider extends AbstractSingletonProvider
{
    /**************************
     *   Instance variables   *
     *************************/

    private ?string $fridgeCleaningAddedForMonth;
    private Schedule $schedule;


    /************************
     *   Instance methods   *
     ***********************/

    /**
     * @param  Date  $from
     * @param  Date  $till
     *
     * @return  Schedule
     */
    public function generate(Date $from, Date $till): Schedule
    {
        $this->schedule = new Schedule($from, $till);

        // If the results aren't cached or stored on a day-by-day basis, it's more efficient to determine tasks
        // per week and per month. Otherwise, tasks should be determined for each date
        $this->addVacuumingAndFridgeCleaningTasks();
        $this->addWindowCleaningTasks();

        return $this->schedule;
    }

    private function processVacuumingDay(Date $date): void
    {
        $this->schedule->addTask($date, new Tasks\VacuumingTask());

        $month = (string) $date->getMonth();

        if ($month !== $this->fridgeCleaningAddedForMonth) {
            // This is the first vacuuming day in this month, also clean the fridge
            $this->schedule->addTask($date, new Tasks\FridgeCleaningTask());

            $this->fridgeCleaningAddedForMonth = $month;
        }
    }

    private function addVacuumingAndFridgeCleaningTasks(): void
    {
        $firstDay = $this->schedule->getFrom();
        $lastDay  = $this->schedule->getTill();
        $period   = $this->schedule->getPeriod();
        $lastYearweekNumber = $lastDay->getWeek()->getYearweekNumber();
        $this->fridgeCleaningAddedForMonth = null;

        for ($week = $firstDay->getWeek();
                $week->getYearweekNumber() <= $lastYearweekNumber;
                $week = $week->getNext())
        {
            $tuesday = $week->getTuesday();

            if ($period->contains($tuesday)) {
                $this->processVacuumingDay($tuesday);
            }

            $thursday = $week->getThursday();

            if ($period->contains($thursday)) {
                $this->processVacuumingDay($thursday);
            }
        }
    }

    private function addWindowCleaningTasks(): void
    {
        $period          = $this->schedule->getPeriod();
        $firstMonth      = $this->schedule->getFrom()->getMonth();
        $lastMonthnumber = $this->schedule->getTill()->getMonth()->getYearmonthNumber();

        for ($month = $firstMonth;
                $month->getYearmonthNumber() <= $lastMonthnumber;
                $month = $month->getNext())
        {
            // The last working day of a month is always part of the last week of the month
            $lastWeek = $month->getLastDay()->getWeek();

            // Find the last workday in that week that's part of this month and also part of the schedule period
            for ($weekday = 5; $weekday >= 1; --$weekday) {
                $workingDay = $lastWeek->getWeekday($weekday);

                if (!$period->contains($workingDay)) {
                    continue;
                }

                if ($workingDay->getMonth()->getYearmonthNumber() === $month->getYearmonthNumber()) {
                    $this->schedule->addTask($workingDay, new Tasks\WindowCleaningTask());

                    break;
                }
            }
        }
    }
}

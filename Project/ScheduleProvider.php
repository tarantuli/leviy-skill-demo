<?php
namespace Project;

use Shared\DateControl\Date;
use Shared\DateControl\Month;
use Shared\Providers\AbstractSingletonProvider;

class ScheduleProvider extends AbstractSingletonProvider
{
    /**************************
     *   Instance variables   *
     *************************/

    private Schedule $schedule;


    /************************
     *   Instance methods   *
     ***********************/

    public function generate(Date $from, Date $till): Schedule
    {
        $this->schedule = new Schedule($from, $till);

        $this->addVacuumingCleaningTasks();
        $this->addFridgeCleaningTasks();
        $this->addWindowCleaningTasks();

        return $this->schedule;
    }

    private function addFridgeCleaningTasks()
    {
        $period          = $this->schedule->getPeriod();
        $firstMonth      = $this->schedule->getFrom()->getMonth();
        $lastMonthnumber = $this->schedule->getTill()->getMonth()->getYearmonthNumber();

        for ($month = $firstMonth;
                $month->getYearmonthNumber() <= $lastMonthnumber;
                $month = $month->getNext())
        {
            $month->setIntervalType(Month::AS_WEEKS);

            foreach ($month as $week) {
                $tuesday = $week->getTuesday();

                if ($tuesday->getMonth()->getYearmonthNumber() === $month->getYearmonthNumber()) {
                    // We found the first Tuesday or Thursday of this month
                    if ($period->contains($tuesday)) {
                        // ... but only add the task if it's part of our selected period
                        $this->schedule->addTask($tuesday, new Tasks\FridgeCleaningTask());
                    }

                    // We're done with this month
                    continue 2;
                }

                $thursday = $week->getThursday();

                if ($thursday->getMonth()->getYearmonthNumber() === $month->getYearmonthNumber()) {
                    // We found the first Tuesday or Thursday of this month
                    if ($period->contains($thursday)) {
                        // ... but only add the task if it's part of our selected period
                        $this->schedule->addTask($thursday, new Tasks\FridgeCleaningTask());
                    }

                    // We're done with this month
                    continue 2;
                }
            }
        }
    }

    private function addVacuumingCleaningTasks(): void
    {
        $firstDay = $this->schedule->getFrom();
        $lastDay  = $this->schedule->getTill();
        $period   = $this->schedule->getPeriod();
        $lastYearweekNumber = $lastDay->getWeek()->getYearweekNumber();

        // Iterate over the weeks spanning the period, checking for each Tuesday and Thursday
        // whether they're within the period
        for ($week = $firstDay->getWeek();
                $week->getYearweekNumber() <= $lastYearweekNumber;
                $week = $week->getNext())
        {
            $tuesday = $week->getTuesday();

            if ($period->contains($tuesday)) {
                $this->schedule->addTask($tuesday, new Tasks\VacuumingTask());
            }

            $thursday = $week->getThursday();

            if ($period->contains($thursday)) {
                $this->schedule->addTask($thursday, new Tasks\VacuumingTask());
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
            // The last working day of a month is always found in the last week of the month
            $lastWeek = $month->getLastDay()->getWeek();

            // Find the last workday in that week that's part of this month
            // and also part of the schedule period
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

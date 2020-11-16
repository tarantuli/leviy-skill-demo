<?php
use Project\ScheduleProvider;
use Shared\Csv\CsvBlob;
use Shared\DateControl\Week;
use Shared\FileControl\File;

require_once 'require_me.php';

try
{
    // Generate a schedule for the period starting with the current Monday, ending in twelve weeks
    $schedule = ScheduleProvider::get()->generate(
        Week::today()->getMonday(),
        Week::today()->getSunday()->getOther(+84)
    );

    // Prepare data for Csv\CsvBlob
    $printableData = [];

    foreach ($schedule as $daySchedule) {
        $printableData[] = $daySchedule->toArray();
    }

    $csvBlob  = CsvBlob::fromIterable($printableData, true);
    $filePath = sprintf('output-files/schedule %s - %s.csv', $schedule->getFrom(), $schedule->getTill());

    if (File::ensureExistence($filePath, $csvBlob)) {
        printf("Created the schedule, saved as %s\n", $filePath);
    }
    else {
        printf("Created the schedule, but failed to save it to %s!\n", $filePath);
    }
}
catch (Exception $exception)
{
    printf("Failed to create the schedule: %s\n", $exception->getMessage());
}

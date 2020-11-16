<?php
use Project\ScheduleProvider;
use Shared\Csv\CsvBlob;
use Shared\DateControl\Date;
use Shared\FileControl\File;

require_once 'require_me.php';

try
{
    // There's multiple ways to interpret "the next three months". (A) from today till the same day of the month, two months in advance,
    // (B) the current month and the two months following it, (C) the current week starting on Monday till the Sunday twelve weeks in advance
    // For now, I've opted for option A
    $from = Date::today();
    $till = Date::today();

    $till->addMonths(2);

    $schedule = ScheduleProvider::get()->generate($from, $till);

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

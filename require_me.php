<?php
use Settings\GlobalSettings;
use Settings\LocalSettings;
use Shared\Logging\ErrorControl;
use Shared\Logging\ToFileLogger;
use Shared\Shared;


/***************
*   Settings   *
***************/

require_once 'Settings/Interfaces/LocalSettingsInterface.php';
require_once 'Settings/LocalSettings.php';
require_once 'Settings/GlobalSettings.php';

$localSettings  = new LocalSettings();
$globalSettings = new GlobalSettings();

chdir(__DIR__);
set_time_limit(0);


/******************
*   Autoloading   *
******************/

/** @noinspection PhpIncludeInspection */
require_once $localSettings->getPathToSharedClasses() . DIRECTORY_SEPARATOR . 'Shared.php';

new Shared();

Shared::get()->addNamespaces($globalSettings->additionalNamespaces());

if (file_exists('vendor/autoload.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once 'vendor/autoload.php';
}


/***************************
*   Class initialization   *
***************************/

// Logging and error control
$errorLogger = new ToFileLogger($localSettings->getLogfilesDirectory());

$errorLogger->setTargetFileType(ToFileLogger::TARGET_FILE_TYPE_DATE);

$errorControl = ErrorControl::get();

$errorControl->setWorkingDirectory(__DIR__);
$errorControl->setErrorLogger($errorLogger);

<?php
namespace Shared;

use Project\GlobalSettings;
use Project\LocalSettings;


/***************
*   Settings   *
***************/

require_once 'Project/Interfaces/LocalSettingsInterface.php';
require_once 'Project/LocalSettings.php';
require_once 'Project/GlobalSettings.php';

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
$errorLogger = new Logging\ToFileLogger($localSettings->getLogfilesDirectory());

$errorLogger->setTargetFileType(Logging\ToFileLogger::TARGET_FILE_TYPE_DATE);

$errorControl = Logging\ErrorControl::get();

$errorControl->setWorkingDirectory(__DIR__);
$errorControl->setErrorLogger($errorLogger);

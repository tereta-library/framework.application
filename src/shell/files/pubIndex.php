<?php declare(strict_types=1);

if (str_ends_with(__FILE__, '/framework.application/src/shell/files/pubIndex.php')) {
    $rootDir = realpath(__DIR__ . '/../../../../../..');
} else {
    $rootDir = realpath(__DIR__ . '/..');
}

require_once "{$rootDir}/vendor/autoload.php";

use Framework\Application\Manager;

Manager::init($rootDir, 'http')->run();
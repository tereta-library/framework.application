#!/bin/php
<?php declare(strict_types=1);

if (str_ends_with(__FILE__, '/framework.application/src/shell/files/cli.php')) {
    $rootDir = realpath(__DIR__ . '/../../../../../..');
} else {
    $rootDir = realpath(__DIR__);
}

require_once "{$rootDir}/vendor/autoload.php";

use Framework\Application\Manager;
use Framework\Cli\Symbol;

if (!function_exists('xdebug_break')) {
    function xdebug_break() {
    }
}

try {
    Manager::init($rootDir, 'cli')->setRouter([
        'setup' => 'Framework\Application\Command\Setup->execute'
    ], true)->run();
} catch (Exception $e) {
    echo Symbol::COLOR_RED . "{$e->getMessage()}\n" . Symbol::COLOR_RESET;
    echo Symbol::COLOR_BRIGHT_WHITE;
    echo "Exception: {$e->getFile()}:{$e->getLine()}\n";
    echo "Backtrace: {$e->getTraceAsString()}";
    echo Symbol::COLOR_RESET;
}

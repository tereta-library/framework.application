#!/bin/php
<?php declare(strict_types=1);

$rootDir = realpath(__DIR__ . '/../../../../../..');
require_once "{$rootDir}/vendor/autoload.php";

use Framework\Application\Manager;
use Framework\Cli\Abstract\Command;

try {
    Manager::init($rootDir, 'cli')->setRouter([
        'setup' => 'Framework\Application\Command\Setup->execute'
    ], true)->run();
} catch (Exception $e) {
    echo Command::SYMBOL_COLOR_RED . "{$e->getMessage()}\n" . Command::SYMBOL_COLOR_RESET;
    echo Command::SYMBOL_COLOR_BRIGHT_WHITE;
    echo "Exception: {$e->getFile()}:{$e->getLine()}\n";
    echo "Backtrace: {$e->getTraceAsString()}";
    echo Command::SYMBOL_COLOR_RESET;
}

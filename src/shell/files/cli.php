#!/bin/php
<?php declare(strict_types=1);

$rootDir = realpath(__DIR__ . '/../../../../../..');
require_once "{$rootDir}/vendor/autoload.php";

use Framework\Application\Manager;

try {
    Manager::init($rootDir, 'cli')->setRouter([
        'setup' => 'Framework\Application\Command\Setup->execute'
    ])->run();
} catch (Exception $e) {
    echo "\033[91m{$e->getMessage()}\033[0m\n";
    echo "\033[37m";
    echo "Exception: {$e->getFile()}:{$e->getLine()}\n";
    echo "Backtrace: {$e->getTraceAsString()}";
    echo "\033[0m";
}

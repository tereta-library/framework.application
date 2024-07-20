<?php declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use Framework\Application\Manager;

Manager::init(realpath(__DIR__ . '/Installer'), 'http')->setRouter([
    'setup' => 'Framework\Cli\Abstract\Command\Setup'
])->run();
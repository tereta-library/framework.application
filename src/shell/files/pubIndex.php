<?php declare(strict_types=1);

if (str_ends_with(__FILE__, '/framework.application/src/shell/files/pubIndex.php')) {
    $rootDir = realpath(__DIR__ . '/../../../../../..');
} else {
    $rootDir = realpath(__DIR__ . '/..');
}

require_once "{$rootDir}/vendor/autoload.php";

use Framework\Application\Manager;

if (!function_exists('xdebug_break')) {
    function xdebug_break() {
    }
}

try {
    Manager::init($rootDir, 'http')->run();
} catch (Exception $e) {
    echo "<html><body><h1>500 Fatal Error</h1><table>" .
        "<tr><th>Message </th><td>{$e->getMessage()}</td></tr>" .
        "<tr><th>Code </th><td>{$e->getCode()}</td></tr>" .
        "<tr><th>Type </th><td>" . get_class($e) . "</td></tr>" .
        "<tr><th>File </th><td>{$e->getFile()}</td></tr>" .
        "<tr><th>Line </th><td>{$e->getLine()}</td></tr>" .
        "<tr><th>Trace </th><td><pre>{$e->getTraceAsString()}</pre></td></tr>" .
        "</table></body></html>";
}

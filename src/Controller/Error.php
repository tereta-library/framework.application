<?php declare(strict_types=1);

namespace Framework\Application\Controller;

use Framework\View\Php\Factory as BlockFactory;
use Framework\View\Php\Template;
use Exception;

/**
 * ···························WWW.TERETA.DEV······························
 * ·······································································
 * : _____                        _                     _                :
 * :|_   _|   ___   _ __    ___  | |_    __ _        __| |   ___  __   __:
 * :  | |    / _ \ | '__|  / _ \ | __|  / _` |      / _` |  / _ \ \ \ / /:
 * :  | |   |  __/ | |    |  __/ | |_  | (_| |  _  | (_| | |  __/  \ V / :
 * :  |_|    \___| |_|     \___|  \__|  \__,_| (_)  \__,_|  \___|   \_/  :
 * ·······································································
 * ·······································································
 *
 * @class Framework\Application\Controller\Error
 * @package Framework\Application\Controller
 * @link https://tereta.dev
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 */
class Error
{
    public function notFound(): string
    {
        header('HTTP/1.0 404 Not Found');

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';

        return (string) (new BlockFactory())->create(
            Template::class, [
                'title' => '404 Not Found',
                'content' => (new BlockFactory())->create(
                    Template::class, [
                        'code' => '404',
                        'message' => '404 Not Found',
                        'method' => $_SERVER['REQUEST_METHOD'],
                        'url' => "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"
                    ]
                )->setTemplate('error.phtml')
            ]
        )->setTemplate('root.phtml');
    }

    public function fatal(?Exception $e = null): string
    {
        header('HTTP/1.1 500');

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';

        return (string) (new BlockFactory())->create(
            Template::class, [
                'title' => '500 Fatal Error',
                'content' => (new BlockFactory())->create(
                    Template::class, [
                        'code' => '500',
                        'message' => $e ? $e->getMessage() : '500 Fatal Error',
                        'method' => $_SERVER['REQUEST_METHOD'],
                        'url' => "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
                        'file' => $e ? $e->getFile() : null,
                        'line' => $e ? $e->getLine() : null,
                        'backtrace' => $e ? $e->getTraceAsString() : null
                    ]
                )->setTemplate('error.phtml')
            ]
        )->setTemplate('root.phtml');
    }
}
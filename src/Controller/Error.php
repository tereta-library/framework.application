<?php declare(strict_types=1);

namespace Framework\Application\Controller;

use Framework\Application\Manager;
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

        $view = Manager::instance()->getView();

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';

        try {
            $view->initialize('error')
                ->getBlockById('content')
                ->assign('title', '404 Not Found')
                ->assign('code', 404)
                ->assign('method', $_SERVER['REQUEST_METHOD'])
                ->assign('url', "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
            return $view->render();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function fatal(?Exception $e = null): string
    {
        header('HTTP/1.1 500');

        $view = Manager::instance()->getView();

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';

        try {
            $view->initialize('error')
                ->getBlockById('content')
                ->assign('title', '500 Fatal Error')
                ->assign('code', 500)
                ->assign('method', $_SERVER['REQUEST_METHOD'])
                ->assign('url', "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}")
                ->assign('message', $e->getMessage())
                ->assign('backTrace', $e->getTraceAsString());
            return $view->render();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
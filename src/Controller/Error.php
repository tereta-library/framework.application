<?php declare(strict_types=1);

namespace Framework\Application\Controller;

use Framework\Application\Manager;
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
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
 */
class Error
{
    public function notFound(): ?string
    {
        $view = Manager::instance()->getView();

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $parsedUrl = parse_url($_SERVER['REQUEST_URI']);

        if (isset($_GET['XDEBUG_SESSION_START'])) {
            $getParams = $_GET;
            unset($getParams['XDEBUG_SESSION_START']);
            $queryString = http_build_query($getParams);
            header('HTTP/1.0 301 XDebug parameter');
            header("Location: {$scheme}://{$_SERVER['HTTP_HOST']}{$parsedUrl['path']}" . ($queryString ? "?{$queryString}" : ''));
            return null;
        }

        try {
            $view->initialize('error')
                ->getBlockById('content')
                ->assign('title', '404 Not Found')
                ->assign('code', 404)
                ->assign('method', $_SERVER['REQUEST_METHOD'])
                ->assign('url', "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

            header('HTTP/1.0 404 Not Found');
            return $view->render();
        } catch (Exception $e) {
            header('HTTP/1.1 500');
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
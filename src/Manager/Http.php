<?php declare(strict_types=1);

namespace Framework\Application\Manager;

use Framework\Application\Interface\Manager;
use Framework\Application\Manager as ParentManager;
use Framework\Controller\Action as ControllerAction;
use Framework\Http\Router as HttpRouter;
use Framework\Http\Router\Expression as HttpRouterExpression;
use Framework\Http\Router\Item as HttpRouterItem;
use Framework\View\Php\Factory as BlockFactory;
use Framework\Application\Controller\Error as ControllerError;
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
 * @class Framework\Application\Manager\Http
 * @package Framework\Application\Manager
 * @link https://tereta.dev
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 */
class Http implements Manager
{
    /**
     * @var HttpRouter|null
     */
    private ?HttpRouter $router = null;

    /**
     * @param string $rootDirectory
     * @param ParentManager $parent
     */
    public function __construct(private string $rootDirectory, private ParentManager $parent)
    {
    }

    /**
     * @param array $configs
     * @return void
     */
    public function setConfigs(array $configs): void
    {
        $theme = 'base';
        BlockFactory::$themeDirectory = $configs['themeDirectory'] ?? "{$this->rootDirectory}/app/view/{$theme}";
    }

    /**
     * @param array $routerExpression
     * @return void
     */
    public function setRouter(array $routerExpression = []): void
    {
        $httpRouterExpression = null;
        if ($routerExpression) {
            $httpRouterExpression = new HttpRouterExpression;
        }

        foreach ($routerExpression as $path => $action) {
            $httpRouterExpression->add(HttpRouterExpression::METHOD_GET, $path, $action);
        }

        $routerRules = [];
        if ($httpRouterExpression) {
            $routerRules[] = $httpRouterExpression;
        }
        $routerRules[] = new HttpRouterItem('Framework\Application\Controller\Error->notFound');

        $this->router = new HttpRouter($routerRules);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        try {
            echo $this->runAction(
                $this->router->run($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])
            );
        } catch (Exception $e) {
            echo (new ControllerError)->fatal($e);
        }
    }

    /**
     * @param string $action
     * @return mixed
     * @throws Exception
     */
    private function runAction(string $action): mixed
    {
        $controllerAction = explode('->', $action);
        $controller = array_shift($controllerAction);
        $action = array_shift($controllerAction);

        $reflectionClass = new ReflectionClass($controller);
        if (!$reflectionClass->hasMethod($action)) {
            throw new Exception("The action method \"$action\" not found in the $controller class");
        }

        $instance = $reflectionClass->newInstance();

        $reflectionMethod = new ReflectionMethod($controller, $action);
        $reflectionMethod->isPublic() || throw new Exception("The action method \"$action\" should be public");

        return $reflectionMethod->invoke($instance);
    }
}
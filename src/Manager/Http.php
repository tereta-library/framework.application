<?php declare(strict_types=1);

namespace Framework\Application\Manager;

use Framework\Application\Interface\Manager;
use Framework\Application\Manager as ParentManager;
use Framework\Controller\Action as ControllerAction;
use Framework\Helper\Config;
use Framework\Helper\File;
use Framework\Http\Router as HttpRouter;
use Framework\Http\Router\Item as HttpRouterItem;
use Framework\Http\Router\Action;
use Framework\Application\Controller\Error as ControllerError;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Framework\Http\Interface\Router as RouterInterface;
use Framework\Helper\PhpDoc;
use ReflectionException;
use Framework\Http\Interface\Controller;
use Framework\View\Html;

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
     * @var Html|null $view
     */
    public ?Html $view = null;

    /**
     * @var array|null $routerTypes
     */
    private ?array $routerTypes = null;

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
     * @param Config $config
     * @return void
     */
    public function setConfig(Config $config): void
    {
        $config->set(
            'themeDirectory',
            $config->get('themeDirectory') ?? "{$this->rootDirectory}/app/view"
        )->set('theme', 'base');

        $this->view = new Html("{$config->get('themeDirectory')}/{$config->get('theme')}");
    }

    /**
     * @return Html|null
     */
    public function getView(): ?Html
    {
        return $this->view;
    }

    /**
     * @param array $routerExpression
     * @return void
     * @throws ReflectionException
     */
    public function setRouter(array $routerExpression = []): void
    {
        $routerConfigs = $this->getControllerRouterRules();

        foreach ($routerConfigs as $router) {
            $params = $this->splitParams($router['route']);
            $routeIdentifier = array_shift($params);
            $routerRules[] = $this->createRouterType($routeIdentifier, $router['action'], $params);
        }

        $routerRules[] = new HttpRouterItem('Framework\Application\Controller\Error->notFound');

        $this->router = new HttpRouter($routerRules);
    }

    /**
     * @param string $route
     * @param array $params
     * @return array
     */
    private function splitParams(string $route, array &$params = []): array
    {
        $route = trim($route);
        if (substr($route, 0, 1) === '"') {
            $delimiterSign = '"';
            $route = substr($route, 1);
        } else {
            $delimiterSign = ' ';
        }

        $offset = 0;
        while (true) {
            $delimiter = strpos($route, $delimiterSign, $offset);
            $escapeCount = 0;
            while (substr($route, $delimiter - 1 - $escapeCount, 1) === '\\') {
                $escapeCount++;
            }

            if ($escapeCount % 2 === 0) {
                break;
            }

            $offset = $delimiter + 1;
        }

        if ($delimiter === false) {
            $params[] = $route;
            return $params;
        }

        $paramItem = substr($route, 0, $delimiter);
        if ($delimiterSign === '"') {
            $paramItem = str_replace("\\\\", "\\", $paramItem);
            $paramItem = str_replace("\\\"", "\"", $paramItem);
        }
        $params[] = $paramItem;

        $this->splitParams(substr($route, $delimiter + 1), $params);

        return $params;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    private function getControllerRouterRules(): array
    {
        $controller = [];
        $action = [];

        foreach ($this->parent->getActiveModules() as $module => $path) {
            $rootDirectory = $this->parent::getRootDirectory();
            $files = File::getFiles("{$rootDirectory}/{$path}/Controller", '/.*\.php/Usi');

            foreach ($files as $file) {
                $controllerItem = "{$module}\\Controller\\" . substr(str_replace('/', '\\', $file), 0, -4);

                $reflectionClass = new ReflectionClass($controllerItem);
                if (!$reflectionClass->implementsInterface(Controller::class)) continue;

                $controller[] = $reflectionClass;
            }
        }

        foreach ($controller as $item) {
            foreach ($item->getMethods() as $reflectionMethod) {
                if (!$reflectionMethod->isPublic()) continue;

                $variables = PhpDoc::getMethodVariables($item->name, $reflectionMethod->name);
                if (!isset($variables['router']) || !$variables['router']) continue;

                $action[] = [
                    'route' => $variables['router'],
                    'action' => $item->name . '->' . $reflectionMethod->name,
                ];
            }
        }

        return $action;
    }

    /**
     * @param string $routerType
     * @param string $action
     * @param array $params
     * @return string
     * @throws ReflectionException
     */
    private function createRouterType(string $routerType, string $action, array $params): RouterInterface
    {
        $routerTypes = $this->getRouterTypes();
        if (!isset($routerTypes[$routerType])) {
            throw new Exception("The router type \"$routerType\" not found");
        }

        $class = $routerTypes[$routerType];

        return new $class($action, $params);
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    private function getRouterTypes(): array
    {
        if (is_array($this->routerTypes)) {
            return $this->routerTypes;
        }

        $routerClasses = [];
        $routerMap = [];

        foreach ($this->parent->getActiveModules() as $module => $path) {
            $rootDirectory = $this->parent::getRootDirectory();
            $files = File::getFiles("{$rootDirectory}/{$path}/Router", '/.*\.php/Usi');

            foreach ($files as $file) {
                $routerClasses[] = "{$module}\\Router\\" . substr(str_replace('/', '\\', $file), 0, -4);
            }
        }

        foreach ($routerClasses as $routerClass) {
            $reflectionClass = new ReflectionClass($routerClass);
            if (!$reflectionClass->implementsInterface(RouterInterface::class)) continue;
            if (!$routerClass::ROUTER) continue;

            $routerMap[$routerClass::ROUTER] = $routerClass;
        }

        return $this->routerTypes = $routerMap;
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
     * @param Action|null $actionClass
     * @return mixed
     * @throws ReflectionException
     */
    private function runAction(?Action $actionClass): mixed
    {
        $controllerAction = explode('->', $actionClass->getAction());
        $controller = array_shift($controllerAction);
        $action = array_shift($controllerAction);

        $reflectionClass = new ReflectionClass($controller);
        if (!$reflectionClass->hasMethod($action)) {
            throw new Exception("The action method \"$action\" not found in the $controller class");
        }

        $instance = $reflectionClass->newInstance();

        $reflectionMethod = new ReflectionMethod($controller, $action);
        $reflectionMethod->isPublic() || throw new Exception("The action method \"$action\" should be public");

        return $reflectionMethod->invokeArgs($instance, $actionClass->getParams());
    }
}
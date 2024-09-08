<?php declare(strict_types=1);

namespace Framework\Application\Manager;

use Framework\Application\Interface\Manager;
use Framework\Application\Manager as ParentManager;
use Framework\Application\Manager\Http\Parameter as HttpParameter;
use Framework\Application\Manager\Http\Parameter\Payload as PayloadParameter;
use Framework\Application\Manager\Http\Parameter\Post as PostParameter;
use Framework\Application\Manager\Http\Parameter\Get as GetParameter;
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
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
 */
class Http implements Manager
{
    const RESOURCE_PREFIX = '/resource/';

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
     * @var Config|null $config
     */
    private ?Config $config = null;

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
        $this->config = $config;
        $config->set(
            'theme',
            $config->get('theme') ?? 'base'
        );
        $config->set(
            'themeDirectory',
            $config->get('themeDirectory') ?? "{$config->get('viewDirectory')}/{$config->get('theme')}/layout"
        );
        $config->set(
            'generatedThemeDirectory',
            $config->get('generatedThemeDirectory') ?? "{$config->get('generatedDirectory')}/{$config->get('theme')}/layout"
        );

        $this->view = new Html(
            $config->get('themeDirectory'),
            $config->get('generatedThemeDirectory')
        );
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

        foreach($this->parent->getClassByExpression('/^Controller\/.*\.php$/Usi') as $item) {
            $reflectionClass = new ReflectionClass($item);
            if (!$reflectionClass->implementsInterface(Controller::class)) continue;

            $controller[] = $reflectionClass;
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
     * @return RouterInterface
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
            if ($this->mapResource($_SERVER['REQUEST_URI'])) {
                return;
            }

            echo $this->runAction(
                $this->router->run($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])
            );
        } catch (Exception $e) {
            echo (new ControllerError)->fatal($e);
        }
    }

    /**
     * @param string $requestUri
     * @return bool
     */
    private function mapResource(string $requestUri): bool
    {
        if (!str_starts_with($requestUri, static::RESOURCE_PREFIX)) {
            return false;
        }

        $resourcePrefix = trim(static::RESOURCE_PREFIX, '/');
        $parsedUri = parse_url($requestUri);
        $resourceUri = substr($parsedUri['path'], strlen(static::RESOURCE_PREFIX));
        $sourceFile = "{$this->rootDirectory}/app/view/{$resourceUri}";
        $publishThemeDir = "{$this->rootDirectory}/pub/{$resourcePrefix}";
        $publishFile = "{$publishThemeDir}/{$resourceUri}";
        $publishFileDir = dirname($publishFile);

        if (!is_file($sourceFile)) {
            header("HTTP/1.0 404 Not Found");
            return true;
        }

        if (!is_dir($publishFileDir)) {
            @mkdir($publishFileDir, 0777, true);
        }

        if (!file_exists($publishFile)) {
            symlink($sourceFile, $publishFile);
        }

        $contentType = mime_content_type($publishFile);
        switch (pathinfo($publishFile, PATHINFO_EXTENSION)) {
            case('css'):
                $contentType = 'text/css';
                break;
            case('js'):
                $contentType = 'text/javascript';
                break;
        }

        header("Content-Type: " . $contentType);
        readfile($publishFile);
        return true;
    }

    /**
     * @param Action|null $actionClass
     * @return mixed
     * @throws ReflectionException
     */
    private function runAction(?Action $actionClass): mixed
    {
        $payloadObject = (new PayloadParameter())->decode(file_get_contents('php://input'));
        $postObject = (new PostParameter($_POST));
        $getObject = (new GetParameter($_GET));

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

        if ($reflectionClass->hasMethod('construct') && $reflectionMethod->getName() != 'construct') {
            $reflectionClass->getMethod('construct')->invoke($reflectionClass->newInstance());
        }

        $invokeArguments = HttpParameter::methodDetection(
            $reflectionMethod, array_merge($actionClass->getParams(), [$payloadObject, $postObject, $getObject])
        );
        return $reflectionMethod->invokeArgs($instance, $invokeArguments);
    }
}
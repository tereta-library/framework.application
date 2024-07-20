<?php declare(strict_types=1);

namespace Framework\Application\Manager;

use Framework\Application\Interface\Manager;
use Framework\Controller\Action as ControllerAction;
use Framework\Http\Router as HttpRouter;
use Framework\Http\Router\Expression as HttpRouterExpression;
use Framework\Http\Router\Item as HttpRouterItem;
use Framework\View\Php\Factory as BlockFactory;
use App\Controller\Error as ControllerError;
use Exception;

/**
 * class Framework\Application\Manager\Http
 */
class Http implements Manager
{
    /**
     * @var HttpRouter|null
     */
    private ?HttpRouter $router = null;

    /**
     * @param string $rootDirectory
     */
    public function __construct(private string $rootDirectory)
    {
    }

    /**
     * @param array $configs
     * @return void
     */
    public function setConfigs(array $configs): void
    {
        BlockFactory::$themeDirectory = $configs['themeDirectory'] ?? realpath($this->rootDirectory . '/view');
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
        $routerRules[] = new HttpRouterItem('App\Controller\Error->notFound');

        $this->router = new HttpRouter($routerRules);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        try {
            echo (new ControllerAction)->runAction(
                $this->router->run($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])
            );
        } catch (Exception $e) {
            echo (new ControllerError)->fatal($e);
        }
    }
}
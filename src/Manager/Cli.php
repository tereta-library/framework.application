<?php declare(strict_types=1);

namespace Framework\Application\Manager;

use Framework\Application\Interface\Manager;
use Framework\Cli\Router as CliRouter;

/**
 * class Framework\Application\Manager\Cli
 */
class Cli implements Manager
{
    private ?CliRouter $router = null;

    public function __construct(private string $rootDirectory)
    {
    }

    public function setConfigs(array $configs): void
    {
    }

    /**
     * @param array $routerExpression
     * @return void
     */
    public function setRouter(array $routerExpression = []): void
    {
        $this->router = new CliRouter($routerExpression);
    }

    public function run(): void
    {
        $this->router->run($_SERVER['argv']);
    }
}
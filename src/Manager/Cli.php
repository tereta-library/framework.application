<?php declare(strict_types=1);

namespace Framework\Application\Manager;

use Framework\Application\Interface\Manager;
use Framework\Cli\Router as CliRouter;
use Framework\Helper\Config;
use Framework\Helper\File;
use Framework\Application\Manager as ParentManager;
use ReflectionClass;
use Framework\Helper\PhpDoc;
use ReflectionException;
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
 * @class Framework\Application\Manager\Cli
 * @package Framework\Application\Manager
 * @link https://tereta.dev
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
 */
class Cli implements Manager
{
    /**
     * @var CliRouter|null
     */
    private ?CliRouter $router = null;

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
    }

    /**
     * @param array $routerExpression
     * @param bool $configurator
     * @return void
     * @throws ReflectionException
     */
    public function setRouter(array $routerExpression = [], bool $configurator = false): void
    {
        if (!$configurator) {
            $this->router = new CliRouter($routerExpression);
            return;
        }

        $commandClasses = [];
        foreach ($this->parent->getActiveModules() as $module => $path) {
            $rootDirectory = $this->parent::getRootDirectory();
            $files = File::getFiles("{$rootDirectory}/{$path}/Command", '/.*\.php/Usi');

            foreach ($files as $file) {
                $commandClasses[] = "{$module}\\Command\\" . substr(str_replace('/', '\\', $file), 0, -4);
            }
        }

        $initialMethods = ['__construct'];

        foreach ($commandClasses as $commandClass) {
            $commandClassReflection = new ReflectionClass($commandClass);
            foreach ($commandClassReflection->getMethods() as $method) {
                if (!$method->isPublic() || $method->isStatic()) continue;
                if (in_array($method->getName(), $initialMethods)) continue;

                $variables = PhpDoc::getMethodVariables($commandClass, $method->getName());
                if (!$variables['cli']) continue;

                $routerExpression[$variables['cli']] = "{$commandClass}->{$method->getName()}";
            }
        }
        $this->router = new CliRouter($routerExpression);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $this->router->run($_SERVER['argv']);
    }
}
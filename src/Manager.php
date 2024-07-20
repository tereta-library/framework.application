<?php declare(strict_types=1);

namespace Framework\Application;

use Framework\Application\Interface\Manager as InterfaceManager;
use Framework\Config\Singleton as Config;
use Framework\Process\Facade as ProcessFacade;
use ReflectionException;
use Exception;

/**
 * class Framework\Application\Manager
 */
class Manager
{
    /**
     * @var bool $isConfigured
     */
    private bool $isConfigured = false;

    /**
     * @var bool $isRouterConfigured
     */
    private bool $isRouterConfigured = false;

    private static ?string $rootDirectory = null;

    private static ?self $instance = null;

    /**
    /**
     * @param string $rootDirectory
     * @param InterfaceManager $managerType
     */
    private function __construct(string $rootDirectory, private InterfaceManager $managerType)
    {
        static::$rootDirectory = $rootDirectory;
    }

    public static function getRootDirectory(): string
    {
        return static::$rootDirectory;
    }

    /**
     * @param string $rootDirectory
     * @param string|object $type
     * @return static
     * @throws Exception
     */
    public static function init(string $rootDirectory, string|object $type): static
    {
        if (!is_object($type)) {
            $type = ucfirst($type);
            $class = "Framework\Application\Manager\\$type";
            $manager = new $class($rootDirectory);
        } else {
            $manager = $type;
        }

        if (!$manager instanceof InterfaceManager) {
            throw new Exception("The application manager type should implement " . InterfaceManager::class);
        }

        static::$instance = new static($rootDirectory, $manager);
        return static::$instance;
    }

    /**
     * @param array $configs
     * @return $this
     * @throws ReflectionException
     */
    public function setConfigs(array $configs = []): static
    {
        $this->isConfigured = true;

        Config::singleton()->addDirectory(
            $configs['configDirectory'] ?? realpath(static::$rootDirectory . '/app/etc')
        );
        ProcessFacade::setPidDirectory(
            $configs['pidsDirectory'] ?? static::$rootDirectory . '/var/pids'
        );

        $this->managerType->setConfigs($configs);

        return $this;
    }

    /**
     * @param array $routerExpression
     * @return $this
     */
    public function setRouter(array $routerExpression = []): static
    {
        $this->isRouterConfigured = true;
        $this->managerType->setRouter($routerExpression);

        return $this;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function run(): void
    {
        if (!$this->isConfigured) {
            $this->setConfigs();
        }

        if (!$this->isRouterConfigured) {
            $this->setRouter();
        }

        $this->managerType->run();
    }
}
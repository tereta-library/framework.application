<?php declare(strict_types=1);

namespace Framework\Application;

use Framework\Application\Interface\Manager as InterfaceManager;
use Framework\Config\Singleton as Config;
use Framework\Process\Facade as ProcessFacade;
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
 * @class Framework\Application\Manager
 * @package Framework\Application
 * @link https://tereta.dev
 * @author Tereta Alexander <tereta.alexander@gmail.com>
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

    /**
     * @var string|null
     */
    private static ?string $rootDirectory = null;

    /**
     * @var Manager|null
     */
    private static ?self $instance = null;

    /**
     * @var array|null
     */
    private ?array $activeModules = null;

    /**
     * @var InterfaceManager
     */
    private InterfaceManager $managerType;

    /**
     * @param string $rootDirectory
     * @param string|object $type
     * @throws Exception
     */
    private function __construct(string $rootDirectory, string|object $type)
    {
        static::$rootDirectory = $rootDirectory;

        if (!is_object($type)) {
            $type = ucfirst($type);
            $class = "Framework\Application\Manager\\$type";
            $manager = new $class($rootDirectory, $this);
        } else {
            $manager = $type;
        }

        if (!$manager instanceof InterfaceManager) {
            throw new Exception("The application manager type should implement " . InterfaceManager::class);
        }

        $this->managerType = $manager;
    }

    /**
     * @return string
     */
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
        static::$instance = new static($rootDirectory, $type);
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
     * @return array
     */
    public function getActiveModules(): array
    {
        if ($this->activeModules !== null) {
            return $this->activeModules;
        }

        $configModules = (new \Framework\Helper\Config('php'))
            ->load(static::$rootDirectory . '/app/etc/modules.php');

        $this->activeModules = [];
        foreach($configModules as $key => $item) {
            if ($item['enabled'] === false) {
                continue;
            }

            $this->activeModules[$key] = $item['path'];
        }

        return $this->activeModules;
    }

    /**
     * @param array $routerExpression
     * @param bool $configurator
     * @return $this
     */
    public function setRouter(array $routerExpression = [], bool $configurator = false): static
    {
        $this->isRouterConfigured = true;

        $this->managerType->setRouter($routerExpression, $configurator);
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
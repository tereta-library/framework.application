<?php declare(strict_types=1);

namespace Framework\Application;

use Framework\Application\Interface\Manager as InterfaceManager;
use Exception;
use Framework\Helper\Config;
use Framework\Helper\File;
use Framework\View\Html as ViewHtml;
use Framework\Database\Singleton;
use Framework\Helper\ArrayManager;
use Framework\Pattern\Observer;

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
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
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
    private InterfaceManager $adapter;

    /**
     * @var Config|null $config
     */
    private ?Config $config = null;

    /**
     * @var Observer|null $events
     */
    private ?Observer $events = null;

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

        $this->adapter = $manager;
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
     * @return $this
     */
    public static function getInstance(): static
    {
        return static::$instance;
    }

    /**
     * It is proxy for manager types http and cli
     *
     * @param array $config
     * @return $this
     * @throws Exception
     */
    public function setConfig(array $config = []): static
    {
        $this->isConfigured = true;

        $configDirectory = $config['configDirectory'] ?? realpath(static::$rootDirectory . '/app/etc');
        $this->config = (new Config('php', $config))->load("{$configDirectory}/config.php");

        $this->setConfigModules();

        $this->setConfigConnection();

        $this->config->set(
            'varDirectory',
            $this->config->get('varDirectory') ?? static::$rootDirectory . '/var'
        );

        $this->config->set(
            'generatedDirectory',
            $this->config->get('generatedDirectory') ?? $this->config->get('varDirectory') . '/generated'
        );

        $this->config->set(
            'publicMedia',
            $this->config->get('publicMedia') ?? static::$rootDirectory . '/pub/media'
        );

        $this->config->set(
            'publicMediaUri',
            $this->config->get('publicMediaUri') ?? '/media'
        );

        $this->config->set(
            'viewDirectory',
            $this->config->get('viewDirectory') ?? static::$rootDirectory . "/app/view"
        );

        $this->adapter->setConfig($this->config);

        $this->getEvents()->dispatch('application.manager.viewConfig', ['manager' => $this]);

        return $this;
    }

    private function setConfigModules()
    {
        $configFiles = [];

        foreach ($this->getActiveModules() as $module => $path) {
            $rootDirectory = static::getRootDirectory();
            $files = File::getFiles("{$rootDirectory}/{$path}/", '/^etc\/config.php$/');


            $files = array_map(function($file) use ($rootDirectory, $path) {
                return "{$rootDirectory}/{$path}/$file";
            }, $files);

            $configFiles = array_merge($configFiles, $files);
        }

        $config = [];
        foreach ($configFiles as $file) {
            $configItem = require($file);

            $config = ArrayManager::merge($config, $configItem);
        }

        $events = $this->config->get('events');
        $this->config->set('events', ArrayManager::merge($events ?? [], $config['events'] ?? []));
        if (isset($config['events'])) {
            unset($config['events']);
        }

        $this->config->set('module', $config);

        $this->getEvents()->dispatch('application.manager.config', ['manager' => $this]);
    }

    /**
     * @return void
     */
    private function setConfigConnection(): void
    {
        $dbConnections = $this->config->get('db');
        if (!$dbConnections) {
            return;
        }

        foreach ($dbConnections as $name => $item) {
            $host = $this->config->get("db.{$name}.host");
            $user = $this->config->get("db.{$name}.user");
            $password = $this->config->get("db.{$name}.password");
            $database = $this->config->get("db.{$name}.database");

            if (!$host) {
                $this->config->set("db.{$name}.host", $host = '127.0.0.1');
            }

            if (!$user) {
                $this->config->set("db.{$name}.user", $user = 'developer');
            }

            if (!$password) {
                $this->config->set("db.{$name}.password", $password = 'developer');
            }

            if (!$database) {
                $this->config->set("db.{$name}.database", $database = 'developer');
            }

            Singleton::createConnection(
                $host,
                $user,
                $password,
                $database,
                $name
            );
        }
    }

    /**
     * @return InterfaceManager
     */
    public function getAdapter(): InterfaceManager
    {
        return $this->adapter;
    }

    /**
     * @return ViewHtml
     */
    public function getView(): ViewHtml
    {
        return $this->adapter->getView();
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param bool $update
     * @return array
     */
    public function getActiveModules(bool $update = false): array
    {
        if (!$update && $this->activeModules !== null) {
            return $this->activeModules;
        }

        $configModules = (new Config('php'))
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
     * @param string $expression
     * @return array
     */
    public function getClassByExpression(string $expression): array
    {
        $return = [];

        foreach ($this->getActiveModules() as $module => $path) {
            $rootDirectory = static::getRootDirectory();
            $files = File::getFiles("{$rootDirectory}/{$path}/", $expression);

            foreach ($files as $file) {
                $classItem = "{$module}\\" . substr(str_replace('/', '\\', $file), 0, -4);

                $return[] = $classItem;
            }
        }

        return $return;
    }

    /**
     * @param array $routerExpression
     * @param bool $configurator
     * @return $this
     */
    public function setRouter(array $routerExpression = [], bool $configurator = false): static
    {
        $this->isRouterConfigured = true;

        $this->adapter->setRouter($routerExpression, $configurator);
        return $this;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        if (!$this->isConfigured) {
            $this->setConfig();
        }

        if (!$this->isRouterConfigured) {
            $this->setRouter();
        }

        $this->adapter->run();
    }

    /**
     * @param bool $reset
     * @return Observer
     */
    private function getEvents(bool $reset = false): Observer
    {
        if (!$reset && $this->events) {
            return $this->events;
        }

        return $this->events = new Observer($this->config->get('events'));
    }
}
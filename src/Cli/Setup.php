<?php declare(strict_types=1);

namespace Framework\Application\Cli;

use Framework\Cli\Interface\Controller;
use Framework\Helper\File;
use Framework\Application\Interface\Module as ModuleInterface;
use Framework\Helper\Config;
use Exception;
use ReflectionClass;
use ReflectionException;
use Framework\Cli\Symbol;
use Framework\Application\Manager;
use Framework\Application\Setup\Installation as SetupInstallation;

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
 * @class Framework\Application\Command\Setup
 * @package Framework\Application\Command
 * @link https://tereta.dev
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
 */
class Setup implements Controller
{
    /**
     * @var string|false
     */
    private string|false $rootDirectory;

    /**
     * @var Config|null
     */
    private ?Config $config = null;

    /**
     *
     */
    public function __construct()
    {
        $this->rootDirectory = Manager::getRootDirectory();
    }

    /**
     * @cli setup
     * @cliDescription Setup and upgrade the configuration structure and modules
     *
     * @return void
     * @throws Exception
     */
    public function setup(): void
    {
        if ($this->modifyComposer()) {
            return;
        }

        $modulesDir = $this->rootDirectory . '/app/module';
        if (!is_dir($modulesDir)) {
            throw new Exception(
                "Directory {$modulesDir} is not created.\n" .
                "Run {$this->rootDirectory}/vendor/tereta/framework.application/src/shell/install.sh to installation"
            );
        }

        $this->setupModules();

        (new SetupInstallation)->setup();
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    private function setupModules(): void
    {
        if ($this->modifyComposer()) {
            return;
        }

        echo Symbol::COLOR_GREEN . "Initialising modules.\n" . Symbol::COLOR_RESET;

        $modules = require($this->rootDirectory . '/vendor/composer/autoload_psr4.php');
        $modulesDir = $this->rootDirectory . '/app/module';
        if (!is_dir($modulesDir)) {
            throw new Exception(
                "Directory {$modulesDir} is not created.\n" .
                "Run {$this->rootDirectory}/vendor/tereta/framework.application/src/shell/install.sh to installation"
            );
        }

        echo Symbol::COLOR_GREEN . "Setup modules.\n" . Symbol::COLOR_RESET;
        echo Symbol::COLOR_GREEN. "Initialising modules.\n" . Symbol::COLOR_RESET;

        $files = File::getFiles($modulesDir, '/.*\/.*\/Module.php/Usi');
        $activeModules = [];

        foreach ($files as $directory) {
            $fullFilePath = "{$modulesDir}/{$directory}";
            $namespace = str_replace('/', '\\', dirname($directory));
            $modules[$namespace] = [dirname($fullFilePath)];
        }

        $initialConfig = [];
        if (file_exists($this->rootDirectory . '/app/etc/modules.php')) {
            $initialConfig = (new Config('php'))->load($this->rootDirectory . '/app/etc/modules.php')->getData();
        }

        foreach ($modules as $namespace => $item) {
            if (!$namespace) continue;
            $pathArray = $this->pathPrepare($item);
            $path = array_shift($pathArray);

            if (!file_exists("{$this->rootDirectory}/{$path}/Module.php")) {
                continue;
            }

            $namespace = rtrim($namespace, '\\');
            $moduleClass = new ReflectionClass($namespace . '\\Module');
            if (!$moduleClass->implementsInterface(ModuleInterface::class)) {
                continue;
            }

            $isActive = true;
            if (isset($initialConfig[$namespace]['enabled']) && !$initialConfig[$namespace]['enabled']) {
                $isActive = false;
            }

            echo Symbol::COLOR_WHITE . "Module {$namespace} found: " . ($isActive ? "[Enabled]" : "[Disabled]") . ".\n" . Symbol::COLOR_RESET;

            $activeModules[$namespace] = [
                'enabled' => $isActive,
                'path' => $path
            ];
        }

        $this->config = (new Config('php', $activeModules))
            ->save($this->rootDirectory . '/app/etc/modules.php');

        echo Symbol::COLOR_GREEN. "Modules successfully initialized.\n" . Symbol::COLOR_RESET;
    }

    /**
     * @param array $values
     * @return array
     */
    private function pathPrepare(array $values): array
    {
        foreach ($values as $key => $item) {
            $item = str_starts_with($item, $this->rootDirectory) ? substr($item, strlen($this->rootDirectory) + 1) : $item;
            $values[$key] = $item;
        }

        return $values;
    }

    /**
     * @return bool
     */
    private function modifyComposer(): bool
    {
        echo Symbol::COLOR_GREEN . "Setup modules.\n" . Symbol::COLOR_RESET;

        $composerFile = "{$this->rootDirectory}/composer.json";
        $data = json_decode(file_get_contents($composerFile), true);

        if (isset($data['autoload']['psr-4'][''])) {
            return false;
        }

        if (!isset($data['autoload'])) {
            $data['autoload'] = [];
        }

        if (!isset($data['autoload']['psr-4'])) {
            $data['autoload']['psr-4'] = [];
        }

        if (!isset($data['autoload']['psr-4'][''])) {
            $data['autoload']['psr-4'][''] = 'app/module';
        }

        file_put_contents($composerFile, json_encode($data, JSON_PRETTY_PRINT));

        $phpArguments = implode(" ", $_SERVER['argv']);
        system("cd {$this->rootDirectory}; composer dump-autoload; php {$phpArguments}");
        return true;
    }
}
<?php declare(strict_types=1);

namespace Framework\Application\Command;

use Framework\Cli\Abstract\Command;
use Framework\Helper\File;
use Framework\Application\Interface\Module as ModuleInterface;
use Framework\Helper\Config;
use Exception;
use ReflectionClass;

/**
 * use Framework\Application\Command\Setup;
 */
class Setup extends Command
{
    private string $rootDirectory;
    private ?Config $config = null;

    public static function getDescription(): string {
        return "Setup and upgrade the configuration structure and modules";
    }

    public function __construct(array $argumentValues)
    {
        $this->rootDirectory = realpath(__DIR__ . '/../../../../..');
        parent::__construct($argumentValues);
    }

    public function execute(): void
    {
        if ($this->modifyComposer()) {
            return;
        }

        $modules = require($this->rootDirectory . '/vendor/composer/autoload_psr4.php');
        $modulesDir = $this->rootDirectory . '/app/module';
        if (!is_dir($modulesDir)) {
            throw new Exception(
                "Directory {$modulesDir} is not created.\n" .
                "Run {$this->rootDirectory}/vendor/tereta/framework.application/src/shell/install.sh to installation"
            );
        }

        echo static::SYMBOL_COLOR_GREEN . "Setup modules.\n" . static::SYMBOL_COLOR_RESET;
        echo static::SYMBOL_COLOR_GREEN . "Initialising modules.\n" . static::SYMBOL_COLOR_RESET;

        $this->setupModules();
    }

    private function setupModules(): void
    {
        if ($this->modifyComposer()) {
            return;
        }

        $modules = require($this->rootDirectory . '/vendor/composer/autoload_psr4.php');
        $modulesDir = $this->rootDirectory . '/app/module';
        if (!is_dir($modulesDir)) {
            throw new Exception(
                "Directory {$modulesDir} is not created.\n" .
                "Run {$this->rootDirectory}/vendor/tereta/framework.application/src/shell/install.sh to installation"
            );
        }

        echo static::SYMBOL_COLOR_GREEN . "Setup modules.\n" . static::SYMBOL_COLOR_RESET;
        echo static::SYMBOL_COLOR_GREEN. "Initialising modules.\n" . static::SYMBOL_COLOR_RESET;

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

            echo Command::SYMBOL_COLOR_WHITE . "Module {$namespace} found: " . ($isActive ? "[Enabled]" : "[Disabled]") . ".\n" . Command::SYMBOL_COLOR_RESET;

            $activeModules[$namespace] = [
                'enabled' => $isActive,
                'path' => $path
            ];
        }

        $this->config = (new Config('php', $activeModules))
            ->save($this->rootDirectory . '/app/etc/modules.php');

        echo static::SYMBOL_COLOR_GREEN. "Modules successfully initialized.\n" . static::SYMBOL_COLOR_RESET;
    }

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
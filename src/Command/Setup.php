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

        $files = File::getFiles($modulesDir, '/.*\/.*\/Module.php/Usi');
        $activeModules = [];
        foreach ($files as $directory) {
            $fullFilePath = "{$modulesDir}/{$directory}";
            if (!is_file($fullFilePath)) continue;
            $namespace = str_replace('/', '\\', dirname($directory));

            $moduleClass = new ReflectionClass($namespace . '\\Module');
            if (!$moduleClass->implementsInterface(ModuleInterface::class)) {
                continue;
            }

            $activeModules[$namespace] = [dirname($fullFilePath)];
        }

        foreach ($activeModules as $key => $item) {
            $pathArray = $this->pathPrepare($item);

            $activeModules[$key] = [
                'enabled' => true,
                'path' => array_shift($pathArray)
            ];
        }

        (new Config('php', $activeModules))->save($this->rootDirectory . '/app/etc/modules.php');
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
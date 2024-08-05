<?php declare(strict_types=1);

namespace Framework\Application\Command;

use Framework\Application\Manager;
use Framework\Cli\Interface\Controller;
use Framework\Cli\Symbol;
use Framework\Helper\Config as ConfigHelper;

/**
 * @class Framework\Application\Command\Config
 */
class Config implements Controller
{
    /**
     * @var string $rootDirectory
     */
    private string $rootDirectory;

    /**
     * @var ConfigHelper $config
     */
    private ConfigHelper $config;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->rootDirectory = Manager::getRootDirectory();
        $this->config = (new ConfigHelper('php'));
        $this->config->load($this->rootDirectory . '/app/etc/config.php');
    }

    /**
     * @cli config:set
     * @cliDescription Set configuration value
     *
     * @param string $key
     * @param string $value
     * @return void
     * @throws \Exception
     */
    public function set(string $key, string $value): void
    {
        $this->config->set($key, $value);
        $this->config->save();

        echo Symbol::COLOR_GREEN . "The setting {$key} configured successfully.\n" . Symbol::COLOR_RESET;
    }
}
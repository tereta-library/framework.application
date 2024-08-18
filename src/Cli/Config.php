<?php declare(strict_types=1);

namespace Framework\Application\Cli;

use Framework\Application\Manager;
use Framework\Cli\Interface\Controller;
use Framework\Cli\Symbol;
use Framework\Helper\Config as ConfigHelper;

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
 * @class Framework\Application\Cli\Config
 * @package Framework\Application\Cli
 * @link https://tereta.dev
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
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
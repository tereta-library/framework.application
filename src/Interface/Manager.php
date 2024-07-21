<?php declare(strict_types=1);

namespace Framework\Application\Interface;

use Framework\Application\Manager as ParentManager;

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
 * @interface Framework\Application\Interface\Manager
 * @package Framework\Application\Interface
 * @link https://tereta.dev
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 */
interface Manager
{
    /**
     * @param string $rootDirectory
     * @param ParentManager $parent
     */
    public function __construct(string $rootDirectory, ParentManager $parent);

    /**
     * @param array $configs
     * @return void
     */
    public function setConfigs(array $configs): void;

    /**
     * @param array $routerExpression
     * @return void
     */
    public function setRouter(array $routerExpression = []): void;

    /**
     * @return void
     */
    public function run(): void;
}
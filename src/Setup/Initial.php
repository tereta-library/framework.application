<?php declare(strict_types=1);

namespace Framework\Application\Setup;

use Framework\Cli\Symbol;
use Framework\Application\Setup\Interface\Upgrade as UpgradeInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Framework\Application\Manager as ApplicationManager;

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
 * @class Framework\Application\Setup\Initial
 * @package Framework\Application\Setup
 * @link https://tereta.dev
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
 */
class Initial
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public function setup(): void
    {
        echo Symbol::COLOR_GREEN . "Setup database.\n" . Symbol::COLOR_RESET;

        $applicationManager = ApplicationManager::instance();
        foreach($applicationManager->getClassByExpression('/^Setup\/.*\.php$/Usi') as $item) {
            $reflectionClass = new ReflectionClass($item);
            if (!$reflectionClass->implementsInterface(UpgradeInterface::class)) continue;

            $setupArray[] = $reflectionClass;
        }

        foreach ($setupArray as $reflectionClass) {
            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                if (!$reflectionMethod->isPublic()) continue;
                $this->runSetup($reflectionClass, $reflectionMethod);
            }
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param ReflectionMethod $reflectionMethod
     * @return void
     * @throws ReflectionException
     */
    private function runSetup(ReflectionClass $reflectionClass, ReflectionMethod $reflectionMethod): void
    {
        $actionIdentifier = "{$reflectionClass->name}->{$reflectionMethod->name}";
        echo Symbol::COLOR_GREEN . "Setup {$actionIdentifier}.\n" . Symbol::COLOR_RESET;
        $reflectionMethod->invoke($reflectionClass->newInstance());
    }
}
<?php declare(strict_types=1);

namespace Framework\Application\Setup;

use Framework\Cli\Symbol;
use Framework\Application\Setup\Abstract\Upgrade as UpgradeAbstract;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Framework\Application\Manager as ApplicationManager;
use Framework\Database\Singleton as DatabaseSingleton;
use PDO;
use Framework\Database\Table;
use Framework\Database\Facade;
use Framework\Database\Factory;
use Framework\Application\Model\Resource\Setup\Collection as ResourceSetupCollection;
use Framework\Application\Model\Setup as SetupModel;
use Framework\Application\Model\Resource\Setup as ResourceSetupModel;
use Framework\Database\Value\Now as ValueNow;

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
class Initialisation
{
    /**
     * @var array
     */
    private array $registeredActions = [];

    /**
     * @return void
     * @throws ReflectionException
     */
    public function setup(): void
    {
        echo Symbol::COLOR_GREEN . "Setup database.\n" . Symbol::COLOR_RESET;

        $connection = DatabaseSingleton::getConnection('default');

        $this->prepareSetup($connection);

        $applicationManager = ApplicationManager::instance();
        $setupArray = [];
        foreach($applicationManager->getClassByExpression('/^Setup\/.*\.php$/Usi') as $item) {
            $reflectionClass = new ReflectionClass($item);
            if (!$reflectionClass->isSubclassOf(UpgradeAbstract::class)) continue;

            $setupArray[] = $reflectionClass;
        }

        $reservedMethod = ['__construct'];

        foreach ($setupArray as $reflectionClass) {
            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                if (!$reflectionMethod->isPublic()) continue;
                if (in_array($reflectionMethod->name, $reservedMethod)) continue;

                $this->runSetup($reflectionClass, $reflectionMethod, $connection);
            }
        }

        echo Symbol::COLOR_GREEN . "Setup database completed.\n" . Symbol::COLOR_RESET;
    }

    /**
     * @param PDO $connection
     * @return void
     */
    private function prepareSetup(PDO $connection): void
    {
        $tableArray = Facade::showTables($connection, 'setup');
        if (!in_array('setup', $tableArray)) {
            $tableQuery = Factory::createTable('setup');
            $tableQuery->addInteger('id')->setAutoIncrement()->setNotNull()->setPrimaryKey();
            $tableQuery->addString('identifier')->setNotNull()->setUnique();
            $tableQuery->addDateTime('createdAt');

            $connection->query($tableQuery->build());
        }

        foreach ((new ResourceSetupCollection) as $item) {
            $this->registeredActions[] = $item->get('identifier');
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param ReflectionMethod $reflectionMethod
     * @param PDO $connection
     * @return void
     * @throws ReflectionException
     */
    private function runSetup(ReflectionClass $reflectionClass, ReflectionMethod $reflectionMethod, PDO $connection): void
    {
        $actionIdentifier = "{$reflectionClass->name}->{$reflectionMethod->name}";
        if (in_array($actionIdentifier, $this->registeredActions)) return;

        echo Symbol::COLOR_GREEN . "Setup {$actionIdentifier}.\n" . Symbol::COLOR_RESET;
        $reflectionMethod->invoke($reflectionClass->newInstanceArgs([
            'connection' => $connection
        ]));

        (new ResourceSetupModel)->save(
            new SetupModel(['identifier' => $actionIdentifier, 'createdAt' => new ValueNow()])
        );
    }
}
<?php declare(strict_types=1);

namespace Framework\Application\Setup;

use Framework\Cli\Symbol;
use Framework\Application\Setup\Interface\Upgrade as UpgradeInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Framework\Application\Manager as ApplicationManager;

/**
 * @class Framework\Application\Setup\Initial
 * @package Framework\Application\Setup
 */
class Initial
{
    public function __construct()
    {
    }

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
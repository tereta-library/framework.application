<?php declare(strict_types=1);

namespace Framework\Application\Interface;

/**
 * @interface Framework\Application\Interface\Manager
 */
interface Manager
{
    public function __construct(string $rootDirectory);

    public function setConfigs(array $configs): void;

    public function setRouter(array $routerExpression = []): void;

    public function run(): void;
}
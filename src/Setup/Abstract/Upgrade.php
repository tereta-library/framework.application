<?php declare(strict_types=1);

namespace Framework\Application\Setup\Abstract;

use PDO;

/**
 * @class Framework\Application\Setup\Abstract\Upgrade
 */
abstract class Upgrade
{
    public function __construct(protected PDO $connection)
    {
    }
}
<?php declare(strict_types=1);

namespace Framework\Application\Cli;

use Framework\Application\Manager as ApplicationManager;
use Exception;
use Framework\Application\Setup\Abstract\Upgrade as AbstractUpgrade;
use Framework\Cli\Interface\Controller;
use Framework\Database\Factory as DatabaseFactory;
use Framework\Cli\Symbol;

/**
 * @class Framework\Application\Cli\Make
 */
class Make implements Controller
{
    /**
     * @var string
     */
    private string $rootDirectory;

    /**
     * @method __construct
     */
    public function __construct()
    {
        $this->rootDirectory = ApplicationManager::getRootDirectory();
    }

    /**
     * @cli make:setup
     * @cliDescription Make model: sample "php cli make:setup Vendor/Module/Setup/Name"
     * @param string $setupClassName Full class name like "Vendor/Module/Setup/Name" or "Vendor/Module/Setup/Space/Name"
     * @param string $setupFunctionName Function name like "createTable" or "newScheme" or "insertValues" or any name to use as the setup function
     * @return void
     * @throws Exception
     */
    public function make(string $setupClassName, string $setupFunctionName): void
    {
        $fullSetupName = ltrim($setupClassName, '/');
        $fullSetupName = ltrim($fullSetupName, '\\');
        $fullSetupName = str_replace('\\', '/', $fullSetupName);
        if (!preg_match('/^([A-Z]{1}[a-z]+)\/([A-Z]{1}[a-z]+)\/Setup(\/[A-Z]{1}[a-z]+)+$/', $fullSetupName)) {
            throw new Exception('Invalid model name, should be in the format of "Vendor/Module/Setup/Name"');
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $setupFunctionName)) {
            throw new Exception('Invalid function name, should be in the format of "createTable" or "newScheme" or any name to use as the setup function');
        }

        $fullSetupName = str_replace('/', '\\', $fullSetupName);

        $modelFile = "{$this->rootDirectory}/app/module/{$fullSetupName}.php";
        $modelFile = str_replace('\\', '/', $modelFile);
        if (is_file($modelFile)) {
            throw new Exception("The {$modelFile} file already exists");
        }

        $classExploded = explode('\\', $fullSetupName);
        $className = array_pop($classExploded);
        $namespace = implode('\\', $classExploded);
        $dateTime = date('Y-m-d H:i:s');
        $content = "<?php declare(strict_types=1);\n\n" .
            "namespace {$namespace};\n\n" .
            "use " . AbstractUpgrade::class . " as AbstractUpgrade;\n" .
            "use " . DatabaseFactory::class . ";\n" .
            "use " . Exception::class . ";\n\n" .
            "/**\n" .
            " * Generated by www.Tereta.dev on {$dateTime}\n" .
            " *\n" .
            " * @class {$fullSetupName}\n" .
            " * @package {$namespace}\n" .
            " */\n" .
            "class {$className} extends AbstractUpgrade \n{\n" .
            "    /**\n" .
            "     * @date {$dateTime} Created\n" .
            "     * @return void\n" .
            "     * @throws Exception\n" .
            "     */\n" .
            "    public function {$setupFunctionName}(){\n" .
            "        \$connection = \$this->connection;\n" .
            "        \n" .
            "        /* @todo Remove this line and implement your setup function */\n" .
            "        throw new Exception('The setup function is not implemented yet'); \n" .
            "        \n" .
            "        \$query = Factory::createTable('testTable');\n" .
            "        \$query->addInteger('id')->setAutoIncrement()->setNotNull()->setPrimaryKey()->setComment('Table row ID');\n" .
            "        \$query->addForeign(\$connection, 'siteId')->foreign('site', 'id')->setComment('Site ID');\n" .
            "        \$query->addString('identifier')->setNotNull()->setComment('identifier');\n" .
            "        \$query->addUnique('siteId', 'identifier');\n" .
            "        \$connection->query(\$query->build());\n" .
            "        \n" .
            "        \$query = Factory::createInsert('testTable')->values([\n" .
            "            'siteId' => '1',\n" .
            "            'identifier' => 'test'\n" .
            "        ])->updateOnDupilicate(['identifier']);\n" .
            "        \$pdoStat = \$connection->prepare(\$query->build());\n" .
            "        \$pdoStat->execute(\$query->getParams());\n" .
            "    }\n" .
            "}\n";

        $modelDirectory = dirname($modelFile);
        if (!is_dir($modelDirectory)) {
            mkdir($modelDirectory, 0777, true);
        }

        file_put_contents($modelFile, $content);

        echo Symbol::COLOR_GREEN . "The \"{$fullSetupName}\" setup class successfully created at the {$modelFile} file\n" . Symbol::COLOR_RESET;
    }
}
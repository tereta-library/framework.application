<?php declare(strict_types=1);

namespace Framework\Application\Controller;

use Framework\Helper\PhpDoc;
use Framework\Http\Interface\Controller;
use Framework\Api\Factory as ApiFactory;
use Exception;
use Framework\Application\Manager;
use ReflectionClass;
use Framework\Api\Interface\Api as ApiInterface;

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
 * @class Framework\Application\Controller\Api
 * @package Framework\Application\Controller
 * @link https://tereta.dev
 * @since 2020-2024
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author Tereta Alexander <tereta.alexander@gmail.com>
 * @copyright 2020-2024 Tereta Alexander
 */
class Api implements Controller
{
    /**
     * @router expression ANY /^\/api\/(\w+)\/(.*)$/Usi
     * @param string $format
     * @param string $identifier
     * @return string
     * @throws Exception
     */
    public function execute(string $format, string $identifier): string
    {
        $routeIdentifier = "{$_SERVER['REQUEST_METHOD']} {$identifier}";
        $apiSourceList = [];
        $apiList = [];
        $payload = file_get_contents('php://input');
        $apiSpecification = (new ApiFactory())->create($format);
        $input = $payload ? $apiSpecification->decode($payload) : $_POST;

        $classList = Manager::getInstance()->getClassByExpression('/^Api\/.*\.php$/Usi');
        foreach ($classList as $item) {
            $reflectionClass = new ReflectionClass($item);
            if (!$reflectionClass->implementsInterface(ApiInterface::class)) continue;
            $apiSourceList[] = $reflectionClass;
        }

        foreach ($apiSourceList as $item) {
            foreach ($item->getMethods() as $reflectionMethod) {
                if (!$reflectionMethod->isPublic()) continue;

                $variables = PhpDoc::getMethodVariables($item->name, $reflectionMethod->name);
                if (!isset($variables['api']) || !$variables['api']) continue;

                $apiList[$variables['api']] = [
                    'class' => $item,
                    'method' => $reflectionMethod
                ];
            }
        }

        try {
            if (!isset($apiList[$routeIdentifier])) {
                throw new Exception("The \"{$routeIdentifier}\" API endpoint not found", 404);
            }

            $apiClassReflection = $apiList[$routeIdentifier]['class'];
            $apiMethodReflection = $apiList[$routeIdentifier]['method'];

            $apiMethodParametersReflection = $apiMethodReflection->getParameters();
            $inputType = null;
            if (count($apiMethodParametersReflection) >= 1) {
                $inputType = $apiMethodParametersReflection[0]->getType()->getName();
            }

            $inputTypeValue = gettype($input);
            if ($inputType && $inputType != $inputTypeValue) {
                throw new Exception("The input type must be \"{$inputType}\", the \"{$inputTypeValue}\" passed for the \"{$apiMethodParametersReflection[0]->name}\" parameter of the {$apiClassReflection->name}::{$apiMethodReflection->name} method.", 500);
            }

            $output = $apiMethodReflection->invoke($apiClassReflection->newInstance(), $input);
        } catch (Exception $e) {
            return $apiSpecification->encode([
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }

        header('Cache-Control: no-cache, no-store, must-revalidate');
        return $apiSpecification->encode($output);
    }
}

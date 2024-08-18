<?php declare(strict_types=1);

namespace Framework\Application\Controller;

use Framework\Helper\PhpDoc;
use Framework\Http\Interface\Controller;
use Framework\Api\Factory as ApiFactory;
use Exception;
use Framework\Application\Manager;
use ReflectionClass;
use Framework\Api\Interface\Api as ApiInterface;
use Framework\Application\Api\Parameter\Payload as PayloadParameter;
use Framework\Application\Api\Parameter\Post as PostParameter;

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
        $apiSourceList = [];
        $payloadObject = (new PayloadParameter())->decode(file_get_contents('php://input'));
        $postObject = (new PostParameter($_POST));
        $apiSpecification = (new ApiFactory())->create($format);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        $classList = Manager::getInstance()->getClassByExpression('/^Api\/.*\.php$/Usi');
        foreach ($classList as $item) {
            $reflectionClass = new ReflectionClass($item);
            if (!$reflectionClass->implementsInterface(ApiInterface::class)) continue;
            $apiSourceList[] = $reflectionClass;
        }

        $apiFound = false;
        foreach ($apiSourceList as $item) {
            foreach ($item->getMethods() as $reflectionMethod) {
                if (!$reflectionMethod->isPublic()) continue;

                $variables = PhpDoc::getMethodVariables($item->name, $reflectionMethod->name);
                if (!isset($variables['api']) || !$variables['api']) continue;

                $apiResultCandiate = $this->fetchApi($requestMethod, $identifier, $variables['api'], $item, $reflectionMethod);
                if ($apiResultCandiate) {
                    $apiFound = $apiResultCandiate;
                }
            }
        }

        try {
            if (!$apiFound) {
                throw new Exception("The \"{$identifier}\" API endpoint not found", 404);
            }

            $apiClassReflection = $apiFound['class'];
            $apiMethodReflection = $apiFound['method'];
            $apiParams = $apiFound['parameters'];

            $apiMethodParametersReflection = $apiMethodReflection->getParameters();
            array_pop($apiMethodParametersReflection);
            foreach ($apiMethodParametersReflection as $parameterKey => $parameterItem) {
                if (!isset($apiParams[$parameterKey])) {
                    throw new Exception(
                        "The \"{$parameterItem->name}\" parameter is required for the \"{$apiMethodReflection->name}\" method of the {$apiClassReflection->name} class.",
                        500
                    );
                }

                $inputType = $parameterItem->getType()->getName();
                $inputTypeValue = gettype($apiParams[$parameterKey]);
                if ($inputType && $inputType != $inputTypeValue) {
                    throw new Exception(
                        "The input type must be \"{$inputType}\", " .
                        "the \"{$inputTypeValue}\" passed for the \"{$apiMethodParametersReflection[0]->name}\" " .
                        "parameter of the {$apiClassReflection->name}::{$apiMethodReflection->name} method.",
                        500
                    );
                }
            }

            $invokeArguments = $this->invokeArgumentsMap($apiMethodReflection, $apiParams, $payloadObject, $postObject);
            $output = $apiMethodReflection->invokeArgs($apiClassReflection->newInstance(), $invokeArguments);
        } catch (Exception $e) {
            return $apiSpecification->encode([
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }

        header('Cache-Control: no-cache, no-store, must-revalidate');
        return $apiSpecification->encode($output);
    }

    /**
     * @param $reflectionMethod
     * @param array $apiParams
     * @param PayloadParameter $payload
     * @param PostParameter $postObject
     * @return array
     */
    private function invokeArgumentsMap($reflectionMethod, array $apiParams, PayloadParameter $payload, PostParameter $postObject): array
    {
        $invokeArguments = [];
        $key = 0;
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $type = $parameter->getType()->getName();
            switch($type) {
                case(PayloadParameter::class):
                    $invokeArguments[] = $payload;
                    continue;
                case(PostParameter::class):
                    $invokeArguments[] = $postObject;
                    continue;
            }

            $invokeArguments[] = $apiParams[$key];
            $key++;
        }

        return $invokeArguments;
    }

    /**
     * @param string $requestMethod
     * @param string $requestIdentifier
     * @param string $apiRule
     * @param $reflectionClass
     * @param $reflectionMethod
     * @return array|null
     */
    private function fetchApi(string $requestMethod, string $requestIdentifier, string $apiRule, $reflectionClass, $reflectionMethod): ?array
    {
        $apiRuleExploded = explode(' ', $apiRule);
        $apiRuleMethod = array_shift($apiRuleExploded);
        if ($apiRuleMethod != 'ANY' && $apiRuleMethod != $requestMethod) {
            return null;
        }

        $apiRuleUrl = trim(array_shift($apiRuleExploded));
        if (!preg_match($apiRuleUrl, $requestIdentifier, $matches)) {
            return null;
        }

        array_shift($matches);

        return [
            'class' => $reflectionClass,
            'method' => $reflectionMethod,
            'parameters' => $matches,
        ];
    }
}

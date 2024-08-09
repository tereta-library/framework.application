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
        $apiSourceList = [];
        $apiList = [];
        $payload = file_get_contents('php://input');
        $apiSpecification = (new ApiFactory())->create($format);
        $input = $apiSpecification->decode($payload);

        $classList = Manager::instance()->getClassByExpression('/^Api\/.*\.php$/Usi');
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
            if (!isset($apiList[$identifier])) {
                throw new Exception("The \"{$identifier}\" API endpoint not found", 404);
            }

            $apiClassReflection = $apiList[$identifier]['class'];
            $apiMethodReflection = $apiList[$identifier]['method'];

            $apiMethodParametersReflection = $apiMethodReflection->getParameters();
            $inputType = null;
            if (count($apiMethodParametersReflection) >= 1) {
                $inputType = $apiMethodParametersReflection[0]->getType()->getName();
            }

            if ($inputType && $inputType != gettype($input)) {
                throw new Exception("The input type must be \"{$inputType}\"", 500);
            }

            $output = $apiMethodReflection->invoke($apiClassReflection->newInstance(), $input);
        } catch (Exception $e) {
            return $apiSpecification->encode([
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }

        return $apiSpecification->encode($output);
    }
}

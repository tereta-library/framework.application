<?php declare(strict_types=1);

namespace Framework\Application\Manager\Http;

/**
 * @class Framework\Application\Manager\Http\Parameter
 */
class Parameter
{
    public static function methodDetection($reflectionMethod, array $params): array
    {
        $parameters = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $type = $parameter->getType()->getName();
            foreach ($params as $key => $param) {
                if ($param instanceof $type) {
                    $parameters[] = $param;
                    unset($params[$key]);
                    break;
                }

                if (gettype($param) === $type) {
                    $parameters[] = $param;
                    unset($params[$key]);
                    break;
                }
            }
        }
        return $parameters;
    }
}
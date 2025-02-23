<?php declare(strict_types=1);

namespace Framework\Application\Helper;

use Exception as BaseException;

/**
 * @package Framework\Application\Helper
 * @class Framework\Application\Helper\Exception
 */
class Exception extends BaseException
{
    /**
     * @param BaseException $e
     * @return string
     */
    public static function getScope(BaseException $e): string
    {
        $exception = explode('\\', get_class($e));
        $nameExists = [];

        $exception = array_map(function(string $value) use (&$nameExists): ?string {
            if ($value == 'Exception') {
                return null;
            }

            $value = strtolower($value);

            if (in_array($value, $nameExists)) {
                return null;
            }

            $nameExists[] = $value;
            return $value;
        }, $exception);
        $exception = array_filter($exception);

        return implode('.', $exception);
    }
}
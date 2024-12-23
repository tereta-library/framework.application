<?php declare(strict_types=1);

namespace Framework\Application\Manager\Http\Parameter;

use Framework\Pattern\ValueObject;

/**
 * @class Framework\Application\Manager\Http\Parameter\Server
 */
class Server extends ValueObject
{
    /**
     * @return string
     */
    public function getAcceptLanguage(): string
    {
        return $this->get('HTTP_ACCEPT_LANGUAGE');
    }

    /**
     * @return string|null
     */
    public function getRemoteIp(): ?string
    {
        return $this->get('HTTP_X_FORWARDED_FOR') ?? $this->get('REMOTE_ADDR') ?? null;
    }

    /**
     * @return string|null
     */
    public function getHttpHost(): ?string
    {
        return $this->get('HTTP_HOST') ?? null;
    }
}
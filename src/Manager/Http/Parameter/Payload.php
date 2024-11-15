<?php declare(strict_types=1);

namespace Framework\Application\Manager\Http\Parameter;

use Framework\Pattern\ValueObject;

/**
 * @class Framework\Application\Manager\Http\Parameter\Payload
 */
class Payload extends ValueObject
{
    /**
     * @param string|null $data
     * @return $this
     */
    public function decode(?string $data): static
    {
        if (!$data) {
            return $this;
        }

        $this->setData(json_decode($data, true) ?? []);
        return $this;
    }
}
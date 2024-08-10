<?php declare(strict_types=1);

namespace Framework\Application\Model\Resource\Setup;

use Framework\Database\Abstract\Resource\Collection as ResourceCollection;
use Framework\Application\Model\Resource\Setup as ResourceModel;
use Framework\Application\Model\Setup as Model;

/**
 * @class Framework\Application\Model\Resource\Setup\Collection
 */
class Collection extends ResourceCollection
{
    public function __construct()
    {
        parent::__construct(ResourceModel::class, Model::class);
    }
}

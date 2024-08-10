<?php declare(strict_types=1);

namespace Framework\Application\Model\Resource;

use Framework\Database\Abstract\Resource\Model as ResourceModel;

/**
 * @class Framework\Application\Model\Resource\Setup
 */
class Setup extends ResourceModel
{
    public function __construct()
    {
        parent::__construct('setup');
    }
}
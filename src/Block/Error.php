<?php declare(strict_types=1);

namespace Framework\Application\Block;

use Framework\View\Php\Template;

/**
 * @class Framework\Application\Block\Error
 */
class Error extends Template
{
    protected function construct(): void
    {
        $this->assign('title')->assign('code')->assign('method')->assign('url')
            ->assign('message')->assign('backTrace');
    }
}
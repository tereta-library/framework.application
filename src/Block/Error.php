<?php declare(strict_types=1);

namespace Framework\Application\Block;

use Framework\View\Php\Template;

/**
 * @class Framework\Application\Block\Error
 */
class Error extends Template
{
    public function render(): string
    {
        $this->assign('title', '404 Not Found');

        return parent::render();
    }
}
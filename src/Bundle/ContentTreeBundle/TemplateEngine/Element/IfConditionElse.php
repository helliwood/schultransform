<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;

/**
 * Class IfConditionElse
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class IfConditionElse extends AbstractElement implements ChildNodeInterface
{
    /**
     * @var string
     */
    protected static string $name = 'else';

    /**
     * @return bool
     */
    public function hasSubContent(): bool
    {
        return true;
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        return null;
    }

    /**
     * @return bool
     */
    public function canRenderChildNodes(): bool
    {
        return false;
    }
}

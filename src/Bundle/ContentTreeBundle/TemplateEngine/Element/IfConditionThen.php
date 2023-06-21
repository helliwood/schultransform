<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;

/**
 * Class IfConditionThen
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class IfConditionThen extends AbstractElement implements ChildNodeInterface
{
    /**
     * @var string
     */
    protected static string $name = 'then';

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

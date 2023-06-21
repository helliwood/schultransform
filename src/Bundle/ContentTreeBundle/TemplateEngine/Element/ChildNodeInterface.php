<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

/**
 * Interface ChildNodeInterface
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
interface ChildNodeInterface
{
    /**
     * @return bool
     */
    public function canRenderChildNodes(): bool;
}

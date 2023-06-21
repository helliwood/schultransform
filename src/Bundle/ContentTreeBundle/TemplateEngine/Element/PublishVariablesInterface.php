<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

/**
 * Interface PublishVariablesInterface
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
interface PublishVariablesInterface
{
    /**
     * @return string[]
     */
    public function publishVariables(): array;
}

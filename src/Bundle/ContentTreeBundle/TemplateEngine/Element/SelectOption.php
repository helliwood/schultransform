<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;

/**
 * Class SelectOption
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class SelectOption extends AbstractElement implements ChildNodeInterface
{
    /**
     * @var string
     */
    protected static string $name = 'option';

    /**
     * @param ElementInterface|null $parentElement
     */
    public function __construct(?ElementInterface $parentElement = null)
    {
        parent::__construct($parentElement);
        $this->createAttribute("value", true);
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

<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeEnum;

/**
 * Class Template
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class Template extends AbstractElement
{
    /**
     * @var string
     */
    protected static string $name = 'template';

    /**
     * Template constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->createAttribute("id", true);
        $this->createAttribute("name", true);
        $this->createAttribute("groups", false);
        $this->createAttribute("form", false, "true", new AttributeTypeEnum(['true', 'false']));
    }

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
        return $this->getDOMElement()->childNodes;
    }
}

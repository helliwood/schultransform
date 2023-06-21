<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type;

use DOMElement;

/**
 * Class AttributeTypeString
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type
 */
class AttributeTypeString implements AttributeTypeInterface
{
    // @codingStandardsIgnoreStart
    /**
     * @param string $elementName
     * @return string
     */
    public function getXsdType(string $elementName): string
    {
        return 'xs:string';
    }

    /**
     * @param DOMElement $schema
     * @param string     $elementName
     */
    public function extendXsd(DOMElement $schema, string $elementName): void
    {
    }
    // @codingStandardsIgnoreEnd
}

<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type;

use DOMElement;

/**
 * Interface AttributeTypeInterface
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type
 */
interface AttributeTypeInterface
{
    /**
     * @param string $elementName
     * @return string
     */
    public function getXsdType(string $elementName): string;

    /**
     * @param DOMElement $schema
     * @param string     $elementName
     */
    public function extendXsd(DOMElement $schema, string $elementName): void;
}

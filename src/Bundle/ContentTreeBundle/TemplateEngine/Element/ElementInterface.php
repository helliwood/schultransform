<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use ArrayObject;
use DOMElement;
use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\AttributeInterface;

/**
 * Interface ElementInterface
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
interface ElementInterface
{
    /**
     * Gibt den xmlnode-Name wieder
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Definiert ob ein Element Unterelemente haben kann, egal ob HTML oder eigene
     * @return bool
     */
    public function hasSubContent(): bool;

    /**
     * @return array|ElementInterface[]
     */
    public static function getPossibleChildren(): array;

    /**
     * @param ElementInterface $parent
     * @return ElementInterface|null
     */
    public function setParent(ElementInterface $parent): ?ElementInterface;

    /**
     * @return ElementInterface|null
     */
    public function getParent(): ?ElementInterface;

    /**
     * @param ElementInterface $element
     * @return ElementInterface|null
     */
    public function addChild(ElementInterface $element): ?ElementInterface;

    /**
     * @return ElementInterface[]
     */
    public function getChildren(): array;

    /**
     * @return array|AttributeInterface[]
     */
    public function getAttributes(): array;

    /**
     * @param string $attributeName
     * @return string|int|null
     */
    public function getAttributeValue(string $attributeName);

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList;

    /**
     * @param DOMElement $node
     * @return ElementInterface
     */
    public function setDOMElement(DOMElement $node): ElementInterface;

    /**
     * @return DOMElement
     */
    public function getDOMElement(): DOMElement;

    /**
     * @return string|null
     */
    public function getScopedId(): ?string;

    /**
     * @param ArrayObject $vars
     * @return ElementInterface
     */
    public function setVariables(ArrayObject $vars): ElementInterface;

    /**
     * @param string $renderMode
     * @return ElementInterface
     */
    public function setRenderMode(string $renderMode): ElementInterface;

    /**
     * @param SiteContent $siteContent
     * @return ElementInterface
     */
    public function setSiteContent(SiteContent $siteContent): ElementInterface;
}

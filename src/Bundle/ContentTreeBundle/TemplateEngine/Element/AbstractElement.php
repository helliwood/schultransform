<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use ArrayObject;
use DOMElement;
use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContentData;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Attribute;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\AttributeInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeEnum;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeString;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use function is_null;
use function strpos;

/**
 * Class AbstractElement
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
abstract class AbstractElement implements ElementInterface
{
    /**
     * @var string
     */
    protected static string $name;

    /**
     * @var ElementInterface|null
     */
    protected ?ElementInterface $parentElement;

    /**
     * @var ElementInterface[]
     */
    protected array $children = [];

    /**
     * @var AttributeInterface[]
     */
    protected array $attributes = [];

    /**
     * @var ArrayObject
     */
    protected ArrayObject $variables;

    /**
     * @var DOMElement|null
     */
    protected ?DOMElement $element;

    /**
     * @var SiteContentData|null
     */
    protected ?SiteContentData $siteContentData;

    /**
     * @var string|null
     */
    protected ?string $scope;

    /**
     * @var string
     */
    protected string $renderMode;

    /**
     * @var SiteContent
     */
    protected SiteContent $siteContent;

    /**
     * AbstractElement constructor.
     *
     * @param ElementInterface|null $parentElement
     */
    public function __construct(?ElementInterface $parentElement = null)
    {
        $this->parentElement = $parentElement;
        $this->createAttribute("scope", false, null, new AttributeTypeString());
        $this->createAttribute("display", false, "true", new AttributeTypeEnum(["false", "true"]));
    }

    /**
     * Gibt den xmlnode-Name wieder
     *
     * @return string
     */
    public static function getName(): string
    {
        return static::$name;
    }

    /**
     * @return bool
     */
    public function hasSubContent(): bool
    {
        return false;
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        return null;
    }

    /**
     * @return ElementInterface[]
     */
    public static function getPossibleChildren(): array
    {
        return [];
    }

    /**
     * @param ElementInterface|null $parent
     * @return $this|ElementInterface|null
     */
    public function setParent(?ElementInterface $parent): ?ElementInterface
    {
        $this->parentElement = $parent;
        return $this;
    }

    /**
     * @return ElementInterface|null
     */
    public function getParent(): ?ElementInterface
    {
        return $this->parentElement;
    }

    /**
     * @param ElementInterface $element
     * @return ElementInterface|null
     */
    public function addChild(ElementInterface $element): ?ElementInterface
    {
        $this->children[] = $element;
        return $this;
    }

    /**
     * @return ElementInterface[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param ElementInterface[] $children
     * @return AbstractElement
     */
    public function setChildren(array $children): AbstractElement
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @return AttributeInterface[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Fügt ein Attribute hinzu
     * @param string                      $name
     * @param bool                        $required
     * @param string|int|null             $defaultValue
     * @param AttributeTypeInterface|null $type
     * @return $this|ElementInterface
     */
    public function createAttribute(string $name, bool $required = false, $defaultValue = null, ?AttributeTypeInterface $type = null): ElementInterface
    {
        $this->attributes[$name] = new Attribute($name, $required, $defaultValue, $type);

        return $this;
    }

    /**
     * @param string $attributeName
     * @return string|int|null
     * @throws Exception
     */
    public function getAttributeValue(string $attributeName)
    {
        if (! isset($this->attributes[$attributeName])) {
            throw new Exception('Attribute "' . $attributeName . '" not set!');
        }

        if (! $this->getDOMElement()->hasAttribute($attributeName) &&
            $this->attributes[$attributeName]->isRequired() &&
            is_null($this->attributes[$attributeName]->getDefaultValue())) {
            throw new Exception('Attribute "' . $attributeName . '" not set!');
        }

        if (! $this->getDOMElement()->hasAttribute($attributeName)) {
            return $this->attributes[$attributeName]->getDefaultValue();
        }

        return $this->getDOMElement()->getAttribute($attributeName);
    }

    /**
     * @return DOMElement
     */
    public function getDOMElement(): DOMElement
    {
        return $this->element;
    }

    /**
     * @param DOMElement $element
     * @return $this|ElementInterface
     */
    public function setDOMElement(DOMElement $element): ElementInterface
    {
        $this->element = $element;
        return $this;
    }

    /**
     * @param ArrayObject $vars
     * @return $this|ElementInterface
     */
    public function setVariables(ArrayObject $vars): ElementInterface
    {
        $this->variables = $vars;
        return $this;
    }

    /**
     * @param string $name
     * @return string|int|null
     */
    public function getVariable(string $name)
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getScopedId(): ?string
    {
        if (! isset($this->attributes['id'])) {
            return null;
        }
        $id = $this->getAttributeValue('id');
        if (isset($this->attributes['scope'])) {
            $scope = $this->getAttributeValue('scope');
            // Check double-scope: do not scope when scope is already in id
            if ($scope && strpos($id, $scope) === 0) {
                return $id;
            }
            return $scope . $id;
        }
        return $this->getAttributeValue('id');
    }

    /**
     * @param string $renderMode
     * @return $this|ElementInterface
     */
    public function setRenderMode(string $renderMode): ElementInterface
    {
        $this->renderMode = $renderMode;
        return $this;
    }

    /**
     * @param SiteContent $siteContent
     * @return $this|ElementInterface
     */
    public function setSiteContent(SiteContent $siteContent): ElementInterface
    {
        $this->siteContent = $siteContent;
        return $this;
    }
}

<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMElement;
use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeEnum;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use function md5;
use function method_exists;
use function preg_replace_callback;
use function str_replace;
use function ucfirst;

/**
 * Class Attribute
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class Attribute extends AbstractElement
{
    /**
     * @var string
     */
    protected static string $name = 'attribute';

    /**
     * @var bool
     */
    protected static bool $hash = false;


    /**
     * @var array|string[]
     */
    protected array $actions = ['append', 'prepend', 'remove'];

    /**
     * Attribute constructor.
     * @param ElementInterface|null $parentElement
     */
    public function __construct(?ElementInterface $parentElement = null)
    {
        parent::__construct($parentElement);
        $this->createAttribute('name', true);
        $this->createAttribute('action', true, null, new AttributeTypeEnum($this->actions));
        $this->createAttribute('value', false);
        $this->createAttribute("hash", false, "false", new AttributeTypeEnum(["false", "true"]));
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $getHtmlTag = function (DOMElement $parent) use (&$getHtmlTag): ?DOMElement {
            if ($parent->namespaceURI !== TemplateEngine::NAMESPACE) {
                return $parent;
            }
            return $getHtmlTag($parent->parentNode);
        };
        $element = $getHtmlTag($this->getDOMElement());
        $methodName = 'action' . ucfirst($this->getAttributeValue('action'));
        if (! method_exists($this, $methodName)) {
            throw new Exception('method ' . $methodName . ' not found');
        }
        $this->$methodName($element);
        return null;
    }

    /**
     * @param DOMElement $element
     * @throws Exception
     */
    public function actionAppend(DOMElement $element): void
    {
        $attrName = $this->getAttributeValue('name');
        $value = $this->getParsedValue();
        if ($element->hasAttribute($attrName)) {
            $element->setAttribute($attrName, $element->getAttribute($attrName) . $value);
        } else {
            $element->setAttribute($attrName, $value);
        }
    }

    /**
     * @param DOMElement $element
     * @throws Exception
     */
    public function actionPrepend(DOMElement $element): voids
    {
        $attrName = $this->getAttributeValue('name');
        $value = $this->getParsedValue();
        if ($element->hasAttribute($attrName)) {
            $element->setAttribute($attrName, $value . $element->getAttribute($attrName));
        } else {
            $element->setAttribute($attrName, $value);
        }
    }

    /**
     * @param DOMElement $element
     * @throws Exception
     */
    public function actionRemove(DOMElement $element): void
    {
        $attrName = $this->getAttributeValue('name');
        $value = $this->getParsedValue();
        if ($element->hasAttribute($attrName)) {
            if ($value) {
                $element->setAttribute($attrName, str_replace($value, '', $element->getAttribute($attrName)));
            } else {
                $element->removeAttribute($attrName);
            }
        }
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getParsedValue(): ?string
    {
        $parsedValue = null;
        $parsedValue = preg_replace_callback("/\\$([a-zA-Z0-9-_]*)/", function ($match) {
            return $this->getVariable($this->getAttributeValue("scope") . $match[1]);
        }, $this->getAttributeValue('value'));

        if ($this->getAttributeValue("hash") === "true") {
            $parsedValue = md5($parsedValue);
        }

        return $parsedValue;
    }
}

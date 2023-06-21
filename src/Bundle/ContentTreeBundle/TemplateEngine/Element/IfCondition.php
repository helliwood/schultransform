<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use function preg_replace_callback;

/**
 * Class IfElse
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class IfCondition extends AbstractElement implements PrepareDocumentInterface
{
    /**
     * @var string
     */
    protected static string $name = 'if';

    /**
     * IfElse constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->createAttribute("condition", true);
    }

    /**
     * @return bool
     */
    public function hasSubContent(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public static function getPossibleChildren(): array
    {
        return [
            IfConditionThen::getName() => IfConditionThen::class,
            IfConditionElse::getName() => IfConditionElse::class
        ];
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $result = $this->getConditionResult();
        $childNodes = null;
        if ($result) {
            if ($this->getDOMElement()->getElementsByTagNameNS(TemplateEngine::NAMESPACE, IfConditionThen::getName())->count() > 0) {
                $childNodes = $this->getDOMElement()->getElementsByTagNameNS(TemplateEngine::NAMESPACE, IfConditionThen::getName())->item(0)->childNodes;
            } else {
                $childNodes = $this->getDOMElement()->childNodes;
            }
        } else {
            if ($this->getDOMElement()->getElementsByTagNameNS(TemplateEngine::NAMESPACE, IfConditionElse::getName())->count() > 0) {
                $childNodes = $this->getDOMElement()->getElementsByTagNameNS(TemplateEngine::NAMESPACE, IfConditionElse::getName())->item(0)->childNodes;
            }
        }
        return $childNodes;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function getConditionResult(): bool
    {
        $isTrue = false;
        $conditionAttr = $this->getAttributeValue("condition");
        $preparedCondition = preg_replace_callback("/[$]{1}([a-zA-Z0-9-_]*)/", function ($match) {
            return "'" . $this->getVariable($this->getAttributeValue("scope") . $match[1]) . "'";
        }, $conditionAttr);
        eval("\$isTrue = $preparedCondition;");
        return $isTrue;
    }

    /**
     * @return callable|void
     */
    public function prepare(): callable
    {
        return function (): void {
            $this->followUp();
        };
    }

    /**
     * @throws Exception
     */
    public function followUp(): void
    {
        if ($this->renderMode !== TemplateEngine::RENDER_MODE_FORM) {
            if ($this->getConditionResult()) {
                foreach ($this->getChildren() as $key => $child) {
                    if ($child instanceof IfConditionElse) {
                        unset($this->children[$key]);
                    }
                }
            } else {
                foreach ($this->getChildren() as $key => $child) {
                    if (! $child instanceof IfConditionElse) {
                        unset($this->children[$key]);
                    }
                }
            }
        }
    }
}

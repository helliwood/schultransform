<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMElement;
use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;

/**
 * Class ForLoopContent
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class ForLoopContent extends AbstractElement implements
    ChildNodeInterface,
    FormExtenderInterface,
    PrepareDocumentInterface
{
    /**
     * @var string
     */
    protected static string $name = 'for-content';

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var FormBuilderInterface|null
     */
    protected ?FormBuilderInterface $formBuilderForChildren;

    /**
     * ForLoopContent constructor.
     * @param ElementInterface|null $parentElement
     */
    public function __construct(?ElementInterface $parentElement = null)
    {
        parent::__construct($parentElement);
        $this->createAttribute("id", true);
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

    /**
     * @return bool
     */
    public function canRenderChildNodes(): bool
    {
        return true;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $forLoopId = $this->getAttributeValue("id");
        $formBuilder->add($forLoopId, FormType::class, ['block_prefix' => 'forloopcontent']);
        $this->formBuilderForChildren = $formBuilder->get($forLoopId);
    }

    /**
     * @return FormBuilderInterface|null
     */
    public function getFormBuilderForChildren(): ?FormBuilderInterface
    {
        return $this->formBuilderForChildren;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDataKey(): string
    {
        return $this->getAttributeValue("id");
    }

    /**
     * @param string[]|string|int|null $data
     */
    public function setData($data): void
    {
        $this->data = $data ?? [];
    }

    /**
     * @throws Exception
     */
    public function prepare(): void
    {
        $id = $this->getAttributeValue("id");
        /** @var DOMElement $templateNode */
        foreach ($this->getDOMElement()->getElementsByTagNameNS(TemplateEngine::NAMESPACE, '*') as $templateNode) {
            $templateNode->setAttribute("scope", $this->getParent()->getScopedId() . "_" . $id . "_");
        }
    }

    /**
     * @return string|null
     */
    public function getFormThemeTemplate(): ?string
    {
        return null;
    }
}

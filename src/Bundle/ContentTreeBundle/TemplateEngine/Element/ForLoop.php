<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMElement;
use DOMNode;
use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use function array_keys;
use function count;

/**
 * Class ForLoop
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class ForLoop extends AbstractElement implements
    FormExtenderInterface,
    PrepareDocumentInterface,
    PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'for';

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var FormBuilderInterface|null
     */
    protected ?FormBuilderInterface $formBuilderForChildren;

    /**
     * ForLoop constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->createAttribute("id", true);
        $this->createAttribute("label", true);
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
            ForLoopContent::getName() => ForLoopContent::class
        ];
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        return $this->getDOMElement()->childNodes;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $forId = $this->getAttributeValue("id");
        $label = $this->getAttributeValue("label");
        $formBuilder->add($forId, FormType::class, ['block_prefix' => 'forloop', 'label' => $label]);
        $this->formBuilderForChildren = $formBuilder->get($forId);
    }

    /**
     * @return FormBuilderInterface|null
     */
    public function getFormBuilderForChildren(): ?FormBuilderInterface
    {
        return $this->formBuilderForChildren;
    }

    /**
     * @param string[]|string|int|null $data
     */
    public function setData($data): void
    {
        $this->data = $data ?? [];
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
     * Move all nodes under for-content and then copy for-content as often as there is data
     * @return callable|void
     */
    public function prepare()
    {
        $forContent = $this->getDOMElement()
            ->ownerDocument
            ->createElementNS(TemplateEngine::NAMESPACE, ForLoopContent::getName());
        $this->getDOMElement()->appendChild($forContent);
        $forContent->setAttribute("id", (count($this->data) > 0 ? array_keys($this->data)[0] : '0'));
        /** @var DOMNode $childNode */
        while ($this->getDOMElement()->childNodes->length > 1) {
            $childNode = $this->getDOMElement()->childNodes->item(0);
            if ($childNode->localName !== ForLoopContent::getName()) {
                $forContent->appendChild($childNode);
            }
        }
        for ($i = 1; $i < count($this->data); $i++) {
            /** @var DOMElement $newNode */
            $newNode = $this->getDOMElement()->appendChild($forContent->cloneNode(true));
            $newNode->setAttribute("id", array_keys($this->data)[$i]);
        }
    }

    /**
     * @return string
     */
    public function getFormThemeTemplate(): string
    {
        return '@ContentTree/forms/forloop.html.twig';
    }
    
    /**
     * @return string[]
     * @throws Exception
     */
    public function publishVariables(): array
    {
        $id = $this->getScopedId();
        $vars = [];
        foreach (array_keys($this->data) as $i) {
            $vars[$id . '_' . $i . '_foreachPointer'] = $i;
        }
        return $vars;
    }
}

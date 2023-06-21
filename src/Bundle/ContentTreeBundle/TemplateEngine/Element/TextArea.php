<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeEnum;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use function nl2br;

/**
 * Class Text
 *
 * @author  Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class TextArea extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'textarea';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * Text constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->createAttribute("id", true);
        $this->createAttribute("label");
        $this->createAttribute("nl2br", false, "true", new AttributeTypeEnum(["true", "false"]));
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
        if ($this->getAttributeValue("nl2br") === "true") {
            $fragment->appendXML('<![CDATA[' . nl2br($this->data) . ']]>');
        } else {
            $fragment->appendXML('<![CDATA[' . $this->data . ']]>');
        }
        return $fragment->childNodes;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $id = $this->getAttributeValue("id");
        $label = $this->getAttributeValue("label");
        $options = ['label' => $label, 'attr' => ['class' => 'js-autoresize', 'style' => 'max-height: 300px;'], 'constraints' => [/*new NotBlank()*/]];

        $formBuilder->add($this->getDataKey(), TextareaType::class, $options);
    }

    /**
     * @return FormBuilderInterface|null
     */
    public function getFormBuilderForChildren(): ?FormBuilderInterface
    {
        return null;
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
     * @param string|int|null $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function publishVariables(): array
    {
        $id = $this->getScopedId();
        return [$id => $this->data];
    }

    /**
     * @return string|null
     */
    public function getFormThemeTemplate(): ?string
    {
        return null;
    }
}

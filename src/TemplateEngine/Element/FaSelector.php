<?php

namespace Trollfjord\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;


/**
 * Class FaSelector
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\TemplateEngine\Element
 */
class FaSelector extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'fa-selector';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * @var SiteService
     */
    protected SiteService $siteService;

    /**
     * Text constructor.
     * @param SiteService $siteService
     */
    public function __construct(SiteService $siteService)
    {
        parent::__construct();
        $this->siteService = $siteService;
        $this->createAttribute("id", true);
        $this->createAttribute("label", true);
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $this->getDOMElement()->nodeValue = htmlspecialchars($this->data);
        return $this->getDOMElement()->childNodes;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $id = $this->getAttributeValue("id");
        $label = $this->getAttributeValue("label") . "--------";
        $options = ['label' => $label, 'constraints' => [/*new NotBlank()*/]];

        $formBuilder->add($this->getDataKey(), TextType::class, $options);
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
     * @param $data
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
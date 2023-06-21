<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use function htmlspecialchars;
use function is_null;

/**
 * Class Select
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class Select extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'select';

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
        $this->createAttribute("placeholder", false);
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
            SelectOption::getName() => SelectOption::class
        ];
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
        $label = $this->getAttributeValue("label");
        $placeholder = $this->getAttributeValue("placeholder");
        $choices = [];
        /** @var SelectOption $child */
        foreach ($this->getChildren() as $child) {
            $choices[$child->getDOMElement()->nodeValue] = $child->getAttributeValue('value');
        }
        $options = ['label' => $label, 'choices' => $choices, 'constraints' => [/*new NotBlank()*/]];
        if (! is_null($placeholder)) {
            $options['placeholder'] = $placeholder;
        }
        $formBuilder->add($this->getDataKey(), ChoiceType::class, $options);
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

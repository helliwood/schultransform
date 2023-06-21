<?php


namespace Trollfjord\TemplateEngine\Element;


use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;


/**
 * Class Select
 *
 * @author  Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\TemplateEngine\Element
 */
class ColorSelect extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    protected $colorKeys = [
        "a",
        "b",
        "c",
        "d",
        "e",
        "f",
        "g",
        "h",
        "i",
        "j",
        "k",
        "l",
        "m",
        "n"
    ];

    /**
     * @var string
     */
    protected static string $name = 'color-select';

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
        $choices = [];
        $choices["default"] = "default";
        foreach ($this->colorKeys as $color) {
            $choices[$color] = $color;
        }
        $options = [
            'label' => $label,
            'choices' => $choices,
            'choice_attr' => function ($val, $key, $index) {
                return ['class' => 'select-color-' . strtolower($key)];
            },
            'constraints' => [/*new NotBlank()*/]];
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

<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use function html_entity_decode;
use function is_array;
use function is_null;
use function is_numeric;
use function is_string;
use function str_pad;
use function strlen;
use const STR_PAD_LEFT;

/**
 * Class Link
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class Link extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'link';

    /**
     * @var array|null
     */
    protected ?array $data = null;

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
        return $this->getDOMElement()->childNodes;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $id = $this->getAttributeValue("id");
        $label = $this->getAttributeValue("label");
        $formBuilder->add($this->getDataKey(), FormType::class, ['label' => $label, 'block_prefix' => 'link']);
        $formBuilder->get($this->getDataKey())->add("type", ChoiceType::class, ['label' => 'Typ:', 'choices' => ['intern' => 'intern', 'extern' => 'extern']]);
        $formBuilder->get($this->getDataKey())->add("link_intern", ChoiceType::class, [
            'label' => 'Link:',
            'placeholder' => '-- Seite wählen --',
            'choices' => $this->siteService->getContentTree4Select(),
            'choice_label' => function (Site $site) {
                $depth = 0;
                $parent = $site->getParent();
                while (! is_null($parent)) {
                    $depth++;
                    $parent = $parent->getParent();
                }
                $siteName = str_pad($site->getName(), strlen($site->getName()) + ($depth * 12), "&nbsp;", STR_PAD_LEFT);
                return html_entity_decode($siteName) . " (" . $site->getId() . ")";
            },
            'choice_value' => function ($site) {
                if (is_string($site)) {
                    return $site;
                }
                return $site ? $site->getId() : null;
            }
        ]);
        $formBuilder->get($this->getDataKey())->add("link_extern", TextType::class, ['label' => 'Link:']);
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
     * @param string[]|string $data
     */
    public function setData($data): void
    {
        $this->data = is_array($data) ? $data : null;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function publishVariables(): array
    {
        $id = $this->getScopedId();
        $title = "";
        $url = null;
        if (isset($this->data['type']) && $this->data['type'] === 'intern') {
            if (isset($this->data['link_intern']) && is_numeric($this->data['link_intern'])) {
                $site = $this->siteService->getSite($this->data['link_intern']);
                if ($site) {
                    $url = $site->getUrl();
                    $title = $site->getName();
                }
            }
        } elseif (isset($this->data['type']) && $this->data['type'] === 'extern') {
            $url = $this->data['link_extern'] ?? null;
            $title = $this->data['link_extern'] ?? null;
        }
        return [
            $id => $url,
            $id . "_type" => $this->data['type'] ?? null,
            $id . "_title" => $title,
            $id . "_id" => (isset($site) ? $site->getId() : null)

        ];
    }

    /**
     * @return string|null
     */
    public function getFormThemeTemplate(): ?string
    {
        return '@ContentTree/forms/link.html.twig';
    }
}

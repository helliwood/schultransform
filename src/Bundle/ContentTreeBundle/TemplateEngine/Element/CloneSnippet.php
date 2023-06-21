<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;

/**
 * Class CloneSnippet
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class CloneSnippet extends AbstractElement implements FormExtenderInterface
{
    /**
     * @var string
     */
    protected static string $name = 'clone';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * @var TemplateEngine
     */
    protected TemplateEngine $templateEngine;

    /**
     * @var  SiteService
     */
    protected SiteService $siteService;

    /**
     * IncludeSnippet constructor.
     * @param TemplateEngine $templateEngine
     * @param SiteService    $siteService
     */
    public function __construct(TemplateEngine $templateEngine, SiteService $siteService)
    {
        parent::__construct();
        $this->createAttribute("id", true);
        $this->templateEngine = $templateEngine;
        $this->siteService = $siteService;
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     * @throws ElementNotFoundException
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $content = null;
        if ($this->data) {
            $siteContent = $this->siteService->getSiteContent($this->data);
            if ($siteContent) {
                $content = $this->templateEngine->renderSiteContent($siteContent);
            }
        }
        if ($content === null && $this->renderMode === TemplateEngine::RENDER_MODE_BACKEND) {
            $content = "<em>KLON NICHT GEFUNDEN!</em>";
        }
        $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
        $fragment->appendXML('<![CDATA[' . $content . ']]>');
        return $fragment->childNodes;
    }


    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $options = ['label' => "Klon", 'constraints' => [new NotBlank()]];

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
     * @param string|int|null $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return string|null
     */
    public function getFormThemeTemplate(): ?string
    {
        return null;
    }
}

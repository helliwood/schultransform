<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine;

use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use Exception as BaseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Snippet;
use Trollfjord\Bundle\ContentTreeBundle\Service\SnippetService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\ChildNodeInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\ElementInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PrepareDocumentInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Template;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\SnippetNotFoundException;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_callable;
use function is_null;
use function json_encode;

/**
 * Class TemplateEngine
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine
 */
class TemplateEngine
{
    /**
     * Template-Tag Namespace
     */
    public const NAMESPACE = "http://helliwood.de";

    /**
     * Form name
     */
    public const SNIPPET_FORM_PREFIX = 'TemplateEngine_Snippet';

    public const RENDER_MODE_FORM = 'form';
    public const RENDER_MODE_PUBLIC = 'public';
    public const RENDER_MODE_BACKEND = 'backend';

    /**
     * @var string
     */
    protected string $templatePath = '';

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var FormFactoryInterface
     */
    protected FormFactoryInterface $formFactory;

    /**
     * @var array
     */
    protected array $elements = [];

    /**
     * @var ElementInterface|null
     */
    protected ?ElementInterface $snippetTree;

    /**
     * @var ArrayObject
     */
    protected ArrayObject $templateVariables;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var SnippetService
     */
    protected SnippetService $snippetService;

    /**
     * @var UrlGeneratorInterface
     */
    protected UrlGeneratorInterface $router;

    /**
     * @var string
     */
    protected string $renderMode;

    /**
     * @var array|string[]
     */
    protected array $formThemeTemplates = [];

    /**
     * @var iterable|PreRenderInterface[]
     */
    protected iterable $preRenderer;

    /**
     * TemplateEngine constructor.
     * @param string                        $template_path
     * @param ContainerInterface            $container
     * @param iterable|PreRenderInterface[] $pre_renderer
     * @param FormFactoryInterface          $formFactory
     * @param EntityManagerInterface        $entityManager
     * @param UrlGeneratorInterface         $router
     * @param SnippetService                $snippetService
     */
    public function __construct(
        string                 $template_path,
        ContainerInterface     $container,
        iterable               $pre_renderer,
        FormFactoryInterface   $formFactory,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface  $router,
        SnippetService         $snippetService
    ) {
        $this->templateVariables = new ArrayObject();
        $this->container = $container;
        $this->preRenderer = $pre_renderer;
        $this->templatePath = $template_path;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->snippetService = $snippetService;
    }

    /**
     * @param SiteContent $siteContent
     * @param string      $renderMode
     * @param bool        $addSnippetVue
     * @return string
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function renderSiteContent(SiteContent $siteContent, string $renderMode = self::RENDER_MODE_PUBLIC, bool $addSnippetVue = true): string
    {
        $this->renderMode = $renderMode;
        $document = $this->getDocument($siteContent->getTemplate());
        $this->prepareDocument($document, $siteContent->getDataAsKeyValueArray());
        $readTree = function (ElementInterface $element) use (&$readTree, $siteContent) {
            foreach ($element->getChildren() as $child) {
                $readTree($child);
            }
            if (! $element instanceof Template &&
                (! $element instanceof ChildNodeInterface || $element->canRenderChildNodes())) {
                $node = $element->getDOMElement();
                $element->setSiteContent($siteContent);
                $renderNodeList = $element->renderNodeList();
                if (! is_null($renderNodeList) && $element->getAttributeValue("display") === "true") {
                    return $this->replaceNodeWithNodeList($node, $renderNodeList);
                }
                $node->parentNode->removeChild($node);
                return -1;
            }
            return $element;
        };
        $readTree($this->snippetTree);

        if ($addSnippetVue && $renderMode === self::RENDER_MODE_BACKEND) {
            $snippetContentPath = $this->router->generate("content_tree_site_sitecontent_content", ['id' => $siteContent->getId()]);
            $snippetFormPath = $this->router->generate("content_tree_site_sitecontent_form", ['id' => $siteContent->getId()]);
            $snippetDeletePath = $this->router->generate("content_tree_site_sitecontent_delete", ['id' => $siteContent->getId()]);
            $snippetUpPath = $this->router->generate("content_tree_site_sitecontent_up", ['id' => $siteContent->getId()]);
            $snippetDownPath = $this->router->generate("content_tree_site_sitecontent_down", ['id' => $siteContent->getId()]);
            $snippetCopyPath = $this->router->generate("content_tree_site_sitecontent_copy", ['id' => $siteContent->getId()]);
            $return = '<snippet 
                            snippet-content-path="' . $snippetContentPath . '" 
                            snippet-form-path="' . $snippetFormPath . '" 
                            snippet-delete-path="' . $snippetDeletePath . '" 
                            snippet-up-path="' . $snippetUpPath . '" 
                            snippet-down-path="' . $snippetDownPath . '" 
                            snippet-copy-path="' . $snippetCopyPath . '"
                            :site-content="' . htmlspecialchars(json_encode($siteContent->toArray(1)), ENT_QUOTES) . '">';
        } else {
            $return = '';
        }
        foreach ($document->firstChild->childNodes as $childNode) {
            $return .= $document->saveHTML($childNode);
        }
        if ($addSnippetVue && $renderMode === self::RENDER_MODE_BACKEND) {
            $return .= '</snippet>';
        }

        return $return;
    }

    /**
     * @param DOMDocument $document
     * @param string[]    $siteContentData
     * @throws ElementNotFoundException
     * @throws Exception
     */
    protected function prepareDocument(DOMDocument $document, array $siteContentData): void
    {
        $this->reset();
        $followUpCalls = [];
        $this->templateVariables['renderMode'] = $this->renderMode;
        $readNode = function (DOMElement $node, ?array $siteContentData, ?ElementInterface $parent = null, int $depth = 0) use (&$readNode, &$followUpCalls): ?ElementInterface {
            $element = null;
            $elementData = $siteContentData;
            if ($node->namespaceURI === self::NAMESPACE) {
                $element = $this->createElement($node, $parent);
                if ($element instanceof FormExtenderInterface) {
                    $elementData = $siteContentData[$element->getDataKey()] ?? null;
                    $element->setData($elementData);
                }
                if ($element instanceof PrepareDocumentInterface) {
                    $followUp = $element->prepare();
                    if (! is_null($followUp)) {
                        if (is_callable($followUp)) {
                            $followUpCalls[] = $followUp;
                        } else {
                            throw new Exception("prepare return is not callable!");
                        }
                    }
                }
                if ($element instanceof PublishVariablesInterface) {
                    foreach ($element->publishVariables() as $key => $value) {
                        $this->templateVariables[$key] = $value;
                    }
                }
            }
            /** @var DOMElement $childNode */
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                $childNode = $node->childNodes->item($i);
                if ($childNode instanceof DOMElement) {
                    $readNode($childNode, is_array($elementData) ? $elementData : null, $element ?? $parent, $depth + 1);
                }
            }
            return $element;
        };
        /** @var ElementInterface $snippetTree */
        $snippetTree = $readNode($document->documentElement, $siteContentData);
        foreach ($followUpCalls as $followUpCall) {
            $followUpCall();
        }
        $this->snippetTree = $snippetTree;
    }

    /**
     * @param DOMElement            $node
     * @param ElementInterface|null $parent
     * @return ElementInterface
     * @throws ElementNotFoundException
     */
    public function createElement(DOMElement $node, ?ElementInterface $parent = null): ElementInterface
    {
        if (! isset($this->elements[$node->localName])) {
            throw new ElementNotFoundException('Element ' . $node->localName . ' not found!');
        }
        /** @var ElementInterface $element */
        $element = $this->container->get($this->elements[$node->localName]);
        $element->setDOMElement($node);
        $element->setRenderMode($this->renderMode);
        $element->setVariables($this->templateVariables);
        if ($parent) {
            $parent->addChild($element);
            $element->setParent($parent);
        }
        return $element;
    }

    /**
     * @param DOMNode     $node
     * @param DOMNodeList $replaceNodes
     * @return int
     */
    public function replaceNodeWithNodeList(DOMNode $node, DOMNodeList $replaceNodes): int
    {
        $nodeAddedOrRemoved = -1;
        $lastNode = $node;
        while (! is_null($replaceNode = $replaceNodes->item(0))) {
            try {
                $nodeAddedOrRemoved++;
                $lastNode->parentNode->insertBefore($replaceNode, $lastNode->nextSibling);
            } catch (BaseException $e) {
                $nodeAddedOrRemoved++;
                $lastNode->parentNode->appendChild($replaceNode);
            }
            $lastNode = $replaceNode;
        }
        $node->parentNode->removeChild($node);
        $nodeAddedOrRemoved--;
        return $nodeAddedOrRemoved;
    }

    /**
     * @param SiteContent $siteContent
     * @return FormInterface
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function getSiteContentForm(SiteContent $siteContent): FormInterface
    {
        $this->renderMode = self::RENDER_MODE_FORM;
        $formBuilder = $this->formFactory->createNamedBuilder(self::SNIPPET_FORM_PREFIX, FormType::class, null, [
            'attr' => ['readonly' => false, 'ref' => 'snippet_edit'],
            'csrf_protection' => true
        ]);
        $siteContentData = $siteContent->getDataAsKeyValueArray();
        $document = $this->getDocument($siteContent->getTemplate());
        $this->prepareDocument($document, $siteContentData);

        $readTree = function (ElementInterface $element, $formBuilder) use (&$readTree) {
            $element->setVariables($this->templateVariables);
            if ($element instanceof FormExtenderInterface) {
                $element->extendForm($formBuilder);
                if ($element->getFormBuilderForChildren()) {
                    $formBuilder = $element->getFormBuilderForChildren();
                }
                if (! is_null($element->getFormThemeTemplate()) &&
                    ! in_array($element->getFormThemeTemplate(), $this->formThemeTemplates)) {
                    $this->formThemeTemplates[] = $element->getFormThemeTemplate();
                }
            }
            foreach ($element->getChildren() as $child) {
                $readTree($child, $formBuilder);
            }
            return $element;
        };
        $readTree($this->snippetTree, $formBuilder);

        $formBuilder->add('TE-action-TE', HiddenType::class);
        $formBuilder->add('TE-action_value-TE', HiddenType::class);
        $formBuilder->setData($siteContentData);
        return $formBuilder->getForm();
    }

    /**
     * @param string $template
     * @return DOMDocument
     */
    public function getDocument(string $template): DOMDocument
    {
        $doc = new DOMDocument('1.0');
        $doc->loadXML($template);

        return $doc;
    }

    /**
     * @param int $snippetId
     * @return DOMDocument
     * @throws SnippetNotFoundException
     */
    public function getDocumentBySnippetId(int $snippetId): DOMDocument
    {
        /** @var Snippet $snippet */
        $snippet = $this->snippetService->getSnippet($snippetId);
        if (! $snippet) {
            throw new SnippetNotFoundException("Snippet " . $snippetId . " not found!");
        }
        return $this->getDocument($snippet->getTemplate());
    }

    /**
     * Reset the local properties
     */
    protected function reset(): void
    {
        $this->snippetTree = null;
        $this->templateVariables = new ArrayObject();
        $this->formThemeTemplates = [];
    }

    /**
     * @param string $elementClass
     * @throws Exception
     */
    public function addElement(string $elementClass): void
    {
        if (! isset($this->elements[$elementClass::getName()])) {
            $this->elements[$elementClass::getName()] = $elementClass;
            foreach ($elementClass::getPossibleChildren() as $childElement) {
                $this->addElement($childElement);
            }
        } else {
            throw new Exception('Element ' . $elementClass::getName() . ' (' . $elementClass . ') already exists!');
        }
    }

    /**
     * @return ElementInterface[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @return string
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * @param string $templatePath
     * @return TemplateEngine
     */
    public function setTemplatePath(string $templatePath): TemplateEngine
    {
        $this->templatePath = $templatePath;
        return $this;
    }

    /**
     * @return iterable|PreRenderInterface[]
     */
    public function getPreRenderer(): iterable
    {
        return $this->preRenderer;
    }

    /**
     * @param iterable|PreRenderInterface[] $preRenderer
     * @return TemplateEngine
     */
    public function setPreRenderer(iterable $preRenderer): TemplateEngine
    {
        $this->preRenderer = $preRenderer;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getFormThemeTemplates(): array
    {
        return $this->formThemeTemplates;
    }

    public function generateSchema(): void
    {
        $schema = new Schema();
        $schema->create($this->getTemplatePath() . "/rs_schema.xsd", $this->getElements(), $this->container);
    }
}

<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use function array_map;
use function count;
use function explode;
use function htmlspecialchars;
use function json_encode;

/**
 * Class ContentArea
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class ContentArea extends AbstractElement
{
    /**
     * @var string
     */
    protected static string $name = 'contentarea';

    /**
     * @var TemplateEngine
     */
    protected TemplateEngine $templateEngine;

    /**
     * @var UrlGeneratorInterface
     */
    protected UrlGeneratorInterface $router;

    /**
     * IncludeSnippet constructor.
     * @param TemplateEngine        $templateEngine
     * @param UrlGeneratorInterface $router
     */
    public function __construct(TemplateEngine $templateEngine, UrlGeneratorInterface $router)
    {
        parent::__construct();
        $this->createAttribute("name", true);
        $this->createAttribute("allowedgroups", true);
        $this->templateEngine = $templateEngine;
        $this->router = $router;
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     * @throws ElementNotFoundException
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $name = $this->getAttributeValue("name");
        $allowedGroups = $this->getAttributeValue('allowedgroups');
        $addPath = $this->router->generate('content_tree_site_sitecontent_add', ['site' => $this->siteContent->getSite()->getId(), 'siteContent' => $this->siteContent->getId(), 'area' => $name]);
        $contentPath = $this->router->generate('content_tree_site_sitecontent_content_area', ['site' => $this->siteContent->getSite()->getId(), 'siteContent' => $this->siteContent->getId(), 'area' => $name]);
        $snippetsPath = $this->router->generate('content_tree_site_sitecontent_snippets', ['allowedGroups' => $allowedGroups]);
        $snippetPastePath = $this->router->generate("content_tree_site_sitecontent_paste", ['site' => $this->siteContent->getSite()->getId(), 'siteContent' => $this->siteContent->getId(), 'area' => $name]);
        $siteContents = $this->siteContent->getChildrenByArea($name);
        if (count($siteContents) > 0) {
            $areaContent = '';
            foreach ($siteContents as $siteContent) {
                $areaContent .= $this->templateEngine->renderSiteContent($siteContent, $this->renderMode);
            }
            $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
            if ($this->renderMode === TemplateEngine::RENDER_MODE_BACKEND) {
                $fragment->appendXML('<![CDATA[<snippet-content-area 
                content-path="' . $contentPath . '" 
                add-path="' . $addPath . '" 
                snippets-path="' . $snippetsPath . '"
                snippet-paste-path="' . $snippetPastePath . '" 
                area="' . $name . '" 
                :allowed-groups="' . htmlspecialchars(json_encode(array_map('trim', explode(',', $allowedGroups)))) . '"
                :site-content="' . htmlspecialchars(json_encode($this->siteContent->toArray(1)), ENT_QUOTES) . '">' . $areaContent . '</snippet-content-area>]]>');
            } else {
                $fragment->appendXML('<![CDATA[' . $areaContent . ']]>');
            }
            return $fragment->childNodes;
        } elseif ($this->renderMode === TemplateEngine::RENDER_MODE_BACKEND) {
            $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
            $fragment->appendXML('<![CDATA[<snippet-content-area 
            content-path="' . $contentPath . '" 
            add-path="' . $addPath . '" 
            snippets-path="' . $snippetsPath . '" 
            snippet-paste-path="' . $snippetPastePath . '" 
            area="' . $name . '" 
            :allowed-groups="' . htmlspecialchars(json_encode(array_map('trim', explode(',', $allowedGroups)))) . '" 
            :site-content="' . htmlspecialchars(json_encode($this->siteContent->toArray(1)), ENT_QUOTES) . '"></snippet-content-area>]]>');
            return $fragment->childNodes;
        }
        return null;
    }
}

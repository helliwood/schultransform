<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMElement;
use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\SnippetNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;

/**
 * Class IncludeSnippet
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class IncludeSnippet extends AbstractElement implements PrepareDocumentInterface
{
    /**
     * @var string
     */
    protected static string $name = 'include';

    /**
     * @var TemplateEngine
     */
    protected TemplateEngine $templateEngine;

    /**
     * IncludeSnippet constructor.
     * @param TemplateEngine $templateEngine
     */
    public function __construct(TemplateEngine $templateEngine)
    {
        parent::__construct();
        $this->createAttribute("id", true);
        $this->createAttribute("snippet", true);
        $this->templateEngine = $templateEngine;
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        return $this->getDOMElement()->childNodes;
    }

    /**
     * @throws Exception
     * @throws SnippetNotFoundException
     */
    public function prepare(): void
    {
        $includeId = $this->getAttributeValue("id");
        $snippetId = $this->getAttributeValue("snippet");
        $snippet = $this->templateEngine->getDocumentBySnippetId($snippetId);
        /** @var DOMElement $templateNode */
        foreach ($snippet->getElementsByTagNameNS(TemplateEngine::NAMESPACE, '*') as $templateNode) {
            $idAttr = $templateNode->getAttribute("id");
            if ($idAttr) {
                $templateNode->setAttribute("id", $includeId . "_" . $idAttr);
            }
            $templateNode->setAttribute("scope", $includeId . "_");
        }
        foreach ($snippet->documentElement->childNodes as $child) {
            $newNode = $this->getDOMElement()->ownerDocument->importNode($child, true);
            $this->getDOMElement()->appendChild($newNode);
        }
    }
}

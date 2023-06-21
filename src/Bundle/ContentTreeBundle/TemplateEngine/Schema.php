<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine;

use DOMDocument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\ElementInterface;
use function count;

/**
 * Class Schema - proof of concept
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine
 */
class Schema
{
    public const PREFIX = 'xs';

    public const NS = 'http://www.w3.org/2001/XMLSchema';

    public const TARGET_NS = 'http://helliwood.de';

    /**
     * Erstellt das Schema
     *
     * @param string             $fileName
     * @param string[]           $elements
     * @param ContainerInterface $container
     */
    public function create(string $fileName, array $elements, ContainerInterface $container): void
    {
        $xsd = new DOMDocument('1.0', 'UTF-8');
        $xsd->preserveWhiteSpace = false;
        $xsd->formatOutput = true;
        $schema = $xsd->createElementNS(self::NS, self::PREFIX . ":schema");
        $schema->setAttribute('xmlns', self::TARGET_NS);
        $schema->setAttribute('targetNamespace', self::TARGET_NS);

        $import = $xsd->createElementNS(self::NS, "import");
        $import->setAttribute("namespace", "http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd");
        $import->setAttribute("schemaLocation", "http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd");
        $schema->appendChild($import);

        $redefine = $xsd->createElementNS(self::NS, "redefine");
        $redefine->setAttribute("schemaLocation", "http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd");
        $schema->appendChild($redefine);

        $complexType = $xsd->createElementNS(self::NS, "complexType");
        $complexType->setAttribute("name", "Block");
        $redefine->appendChild($complexType);

        $complexContent = $xsd->createElementNS(self::NS, "complexContent");
        $complexType->appendChild($complexContent);

        $extension = $xsd->createElementNS(self::NS, "extension");
        $extension->setAttribute("base", "Block");
        $complexContent->appendChild($extension);

        $redefineSequence = $xsd->createElementNS(self::NS, "sequence");
        $redefineSequence->setAttribute("minOccurs", 0);
        $extension->appendChild($redefineSequence);

        $any = $redefineSequence->appendChild($xsd->createElementNS(self::NS, "any"));
        $any->setAttribute("processContents", "strict");
        $any->setAttribute("namespace", "http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd");
        $redefineSequence->appendChild($xsd->createElementNS(self::NS, "group"))->setAttribute("ref", "elements");

        $elementsGroup = $schema->appendChild($xsd->createElementNS(self::NS, "group"));
        $elementsGroup->setAttribute("name", "elements");
        $elementsGroupChoice = $elementsGroup->appendChild($xsd->createElementNS(self::NS, "choice"));

        foreach ($elements as $element) {
            /** @var ElementInterface $element */
            $element = $container->get($element);
            $elementRef = $xsd->createElementNS(self::NS, "element");
            $elementRef->setAttribute("ref", $element::getName());
            $elementsGroupChoice->appendChild($elementRef);
            $elementNode = $xsd->createElementNS(self::NS, "element");
            $elementNode->setAttribute('name', $element::getName());
            if ($element->hasSubContent()) {
                $complexType = $elementNode->appendChild($xsd->createElementNS(self::NS, "complexType"));
                $complexContent = $complexType->appendChild($xsd->createElementNS(self::NS, "complexContent"));
                $extension = $complexContent->appendChild($xsd->createElementNS(self::NS, "extension"));
                $extension->setAttribute("base", "Block");
                $complexType = $extension;
            } else {
                $complexType = $elementNode->appendChild($xsd->createElementNS(self::NS, "complexType"));
            }
            if (count($element->getAttributes()) > 0) {
                foreach ($element->getAttributes() as $attribute) {
                    $attributeNode = $xsd->createElementNS(self::NS, "attribute");
                    $attributeNode->setAttribute("name", $attribute->getName());
                    $attributeNode->setAttribute("type", $attribute->getType()->getXsdType($element::getName() . "_" . $attribute->getName()));
                    $attributeNode->setAttribute("use", $attribute->isRequired() ? "required" : "optional");
                    $complexType->appendChild($attributeNode);
                    $attribute->getType()->extendXsd($schema, $element::getName() . "_" . $attribute->getName());
                }
            }
            $schema->appendChild($elementNode);
        }
        $schema->appendChild($elementsGroup);
        $xsd->appendChild($schema);
        $xsd->save($fileName);
    }
}

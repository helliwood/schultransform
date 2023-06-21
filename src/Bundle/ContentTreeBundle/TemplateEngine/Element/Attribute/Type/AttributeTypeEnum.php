<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type;

use DOMElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Schema;

/**
 * Class AttributeTypeEnum
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type
 */
class AttributeTypeEnum implements AttributeTypeInterface
{
    /**
     * @var string[]
     */
    protected array $enum = [];

    /**
     * AttributeTypeEnum constructor.
     * @param string[] $enum
     */
    public function __construct(array $enum)
    {
        $this->enum = $enum;
    }

    /**
     * @param string $elementName
     * @return string
     */
    public function getXsdType(string $elementName): string
    {
        return $elementName . "_type";
    }

    /**
     * @param DOMElement $schema
     * @param string     $elementName
     */
    public function extendXsd(DOMElement $schema, string $elementName): void
    {
        $simpleType = $schema->appendChild($schema
            ->ownerDocument
            ->createElementNS(Schema::NS, "simpleType"));
        $simpleType->setAttribute("name", $elementName . "_type");
        $restriction = $simpleType->appendChild($schema
            ->ownerDocument
            ->createElementNS(Schema::NS, "restriction"));
        foreach ($this->enum as $value) {
            $restriction->appendChild($schema
                ->ownerDocument
                ->createElementNS(Schema::NS, "enumeration"))
                ->setAttribute("value", $value);
        }
    }
}

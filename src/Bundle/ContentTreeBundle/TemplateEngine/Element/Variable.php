<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeEnum;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use function md5;
use function preg_replace_callback;

/**
 * Class Variable
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class Variable extends AbstractElement
{
    /**
     * @var string
     */
    protected static string $name = 'variable';


    /**
     * @var bool
     */
    protected static bool $hash = false;


    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * Text constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->createAttribute("name", true);
        $this->createAttribute("hash", false, "false", new AttributeTypeEnum(["false", "true"]));
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     */
    public function renderNodeList(): ?DOMNodeList
    {

        //always used scoped value
        $valueName = $this->getAttributeValue("scope") . $this->getAttributeValue("name");


        $val = $this->getVariable($valueName);
        if (! $val) {
            $val = $this->getParsedValue();
        }
        if ($this->getAttributeValue("hash") === "true") {
            $val = md5($val);
        }
        $this->getDOMElement()->nodeValue = $val;
        return $this->getDOMElement()->childNodes;
    }


    /**
     * @return string|null
     * @throws Exception
     */
    public function getParsedValue(): ?string
    {
        $parsedValue = null;
        $parsedValue = preg_replace_callback("/\\$([a-zA-Z0-9-_]*)/", function ($match) {
            return $this->getVariable($this->getAttributeValue("scope") . $match[1]);
        }, $this->getAttributeValue('name'));

        if ($this->getAttributeValue("hash") === "true") {
            $parsedValue = md5($parsedValue);
        }

        return $parsedValue;
    }
}

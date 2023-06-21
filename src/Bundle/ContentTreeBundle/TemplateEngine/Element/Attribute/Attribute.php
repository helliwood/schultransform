<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute;

use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeString;

/**
 * Class Attribute
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute
 */
class Attribute implements AttributeInterface
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var bool
     */
    protected bool $required;

    /**
     * @var string|int|null
     */
    protected $defaultValue;

    /**
     * @var AttributeTypeInterface
     */
    protected AttributeTypeInterface $type;

    /**
     * Attribute constructor.
     * @param string                      $name
     * @param bool                        $required
     * @param null                        $defaultValue
     * @param AttributeTypeInterface|null $type
     */
    public function __construct(string $name, bool $required = false, $defaultValue = null, ?AttributeTypeInterface $type = null)
    {
        $this->name = $name;
        $this->required = $required;
        $this->defaultValue = $defaultValue;
        $this->type = $type ?? new AttributeTypeString();
    }

    /**
     * Gibt den Attribute-Name wieder
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Prüft den required Zustand
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Gibt den Attribute-Type wieder
     *
     * @return AttributeTypeInterface
     */
    public function getType(): AttributeTypeInterface
    {
        return $this->type;
    }

    /**
     * @return string|int|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string|int|null $defaultValue
     * @return AttributeInterface
     */
    public function setDefaultValue($defaultValue): AttributeInterface
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }
}

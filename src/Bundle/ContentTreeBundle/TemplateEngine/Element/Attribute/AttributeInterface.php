<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute;

use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeInterface;

/**
 * Interface AttributeInterface
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute
 */
interface AttributeInterface
{
    /**
     * Gibt den Attribute-Name wieder
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Prüft den required Zustand
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * Gibt einen Default-Wert wieder
     *
     * @return string|int|null
     */
    public function getDefaultValue();

    /**
     * Gibt den Attribute-Type wieder
     *
     * @return AttributeTypeInterface
     */
    public function getType(): AttributeTypeInterface;
}

<?php

namespace Trollfjord\Bundle\GlossaryBundle\Service;


/**
 * Interface RegexServiceInterface
 * @package Trollfjord\Bundle\GlossaryBundle\
 * @author Juan Mayoral <mayoral@helliwood.com>
 */
interface RegexServiceInterface
{
    /**
     * @param string|null $content
     * @return string|null
     */
    public function applyGlossary(?string $content): ?string;


}

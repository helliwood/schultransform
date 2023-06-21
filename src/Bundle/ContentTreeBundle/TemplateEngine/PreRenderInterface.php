<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine;

/**
 * Interface PreRenderInterface
 *
 * @author Maurice Karg <karg@helliwood.com>
 * @date   2021-09-24 12:17
 */
interface PreRenderInterface
{
    /**
     * @param string      $renderMode
     * @param string|null $content
     * @return string|null
     */
    public function preRenderContent(string $renderMode, ?string $content): ?string;
}

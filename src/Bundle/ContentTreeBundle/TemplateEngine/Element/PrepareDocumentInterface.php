<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

/**
 * Interface PrepareDocumentInterface
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
interface PrepareDocumentInterface
{
    /**
     * @return callable|void Call after complete document is prepared
     */
    public function prepare();
}

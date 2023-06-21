<?php

namespace Trollfjord\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Base64 extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('base64_encode', 'base64_encode'),
            new TwigFilter('base64_decode', 'base64_decode')
        ];
    }
}
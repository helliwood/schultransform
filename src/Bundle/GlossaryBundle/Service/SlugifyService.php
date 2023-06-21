<?php

namespace Trollfjord\Bundle\GlossaryBundle\Service;


class SlugifyService
{

    /**
     * @param string|null $text
     * @return string|null
     */
    public static function slugify(?string $text): ?string
    {
        setlocale(LC_ALL, "de_DE.utf-8");
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return null;
        }

        return $text;
    }

}
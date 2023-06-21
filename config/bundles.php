<?php
/**
 * Trollfjord Web Platform - Bundles config
 *
 * @author Maurice Karg <karg@helliwood.com>
 */

if (file_exists(__DIR__ . '/../src/Core/config/bundles.php')) {
    $coreBundles = require __DIR__ . '/../src/Core/config/bundles.php';
} elseif (file_exists(__DIR__ . '/../vendor/trollfjord/core/config/bundles.php')) {
    $coreBundles = require __DIR__ . '/../vendor/trollfjord/core/config/bundles.php';
} else {
    throw new \Exception('core dir not found!');
}

return array_merge($coreBundles, [
    Trollfjord\Bundle\ContentTreeBundle\ContentTreeBundle::class => ['all' => true],
    Trollfjord\Bundle\MediaBaseBundle\MediaBaseBundle::class => ['all' => true],
    Trollfjord\Bundle\PublicUserBundle\PublicUserBundle::class => ['all' => true],
    Trollfjord\Bundle\TFSecurityBundle\TFSecurityBundle::class => ['all' => true],
    Trollfjord\Bundle\QuestionnaireBundle\QuestionnaireBundle::class => ['all' => true],
    Trollfjord\Bundle\GlossaryBundle\GlossaryBundle::class => ['all' => true],
    Trollfjord\Bundle\CookieBundle\CookieBundle::class => ['all' => true],
    Endroid\QrCodeBundle\EndroidQrCodeBundle::class => ['all' => true]
]);

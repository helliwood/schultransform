<?php
/**
 * HwmMedia Module
 *
 * @link http://git.helliwood.de/ccbuchner/digitales_portfolio
 */
namespace Trollfjord\Bundle\MediaBaseBundle\Service;

/**
 * Interface MediaServiceInterface
 * @package Trollfjord\Bundle\MediaBaseBundle\Service
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
interface MediaServiceInterface
{

    /**
     *
     * @return Array
     */
    public function isMediaUsed($id);

}
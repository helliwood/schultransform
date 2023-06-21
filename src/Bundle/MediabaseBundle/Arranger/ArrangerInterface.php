<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Arranger;

use Trollfjord\Bundle\MediaBaseBundle\Storage\StorageInterface;
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * ArrangerInterface
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
interface ArrangerInterface
{

    /**
     * Set Storage
     *
     * @param StorageInterface $storage            
     */
    public function setStorage(StorageInterface $storage);

    /**
     * Get Storage
     *
     * @return StorageInterface
     */
    public function getStorage();

    /**
     * Respone for Media
     *
     * @param Request $request
     * @param MediaInterface $media            
     * @param string $variation            
     * @return Response|null
     */
    public function getResponse(Request $request, MediaInterface $media, $variation = null);

    /**
     * Respone for Media-Thumbnail
     *
     * @param MediaInterface $media
     * @param string $variation
     * @return Response|null
     */
    public function getThumbnailResponse(Request $request, MediaInterface $media, $variation = null);
}
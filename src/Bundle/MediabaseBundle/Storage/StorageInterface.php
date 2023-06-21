<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Storage;

use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;

/**
 * Interface StorageInterface
 * @package Trollfjord\Bundle\MediaBaseBundle\Storage
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
interface StorageInterface
{

    /**
     * Read data
     *
     * @param MediaInterface $media
     * @param $variation
     * @return string|bool            
     */
    public function read(MediaInterface $media, $variation = null);

    /**
     * Write data
     *
     * @param MediaInterface $media
     * @param $variation
     * @return bool           
     */
    public function write(MediaInterface $media, $variation = null);

    /**
     * Write data
     *
     * @param mixed $data
     * @param Media $media
     * @param mixed $variation
     * @return bool
     */
    public function writeData($data, MediaInterface $media, $variation = null);

    /**
     * Delete data
     *
     * @param Media $media            
     * @param $variation
     * @param bool $deleteAll
     * @return bool
     */
    public function delete(MediaInterface $media, $variation = null, $deleteAll = false);
    
    /**
     * Checks if media exists
     * 
     * @param Media $media
     * @param string $variation
     * @return bool
     */
    public function exists(MediaInterface $media, $variation = null);
    
    /**
     * Gets file modification time
     * 
     * @param MediaInterface $media
     * @param unknown $variation
     */
    public function getModificationTime(MediaInterface $media, $variation = null);

    /**
     * @param MediaInterface $media
     * @param MediaInterface $to
     * @return mixed
     */
    public function move(MediaInterface $media, MediaInterface $to=null);

    /**
     * @param MediaInterface $folder
     * @return mixed
     */
    public function renameFolder(MediaInterface $folder);

    /**
     * @param MediaInterface $folder
     * @return mixed
     */
    public function creatFolder(MediaInterface $folder);

    public function getFileInfos(MediaInterface $media, $variation = null): array;
}
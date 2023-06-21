<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Entity;

use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * MediaInterface
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
interface MediaInterface
{

    public function getId();

    public function setId($id);

    public function getName();

    public function setName($name);

    public function getOldName(): ?string;

    public function setOldName(string $changedName);

    public function getIsDirectory(): bool;

    public function setIsDirectory($isDirectory);

    public function getMimeType();

    public function setMimeType($mimeType);

    public function getExtension();

    public function setExtension($extension);

    public function getCreationDate();

    public function setCreationDate($creationDate);

    public function getFileSize();
    
    public function setFileSize($fileSize);

    /**
     * @return UploadedFile|null
     */
    public function getFile(): ?UploadedFile;

    /**
     * @param UploadedFile|null $file
     * @return Media
     */
    public function setFile(?UploadedFile $file): Media;

    /**
     * @return UploadedFile|null
     */
    public function getThumbnail(): ?UploadedFile;

    /**
     * @param UploadedFile|null $thumbnail
     * @return Media
     */
    public function setThumbnail(?UploadedFile $thumbnail): Media;
    
    /**
     *
     * @return MediaInterface
     */
    public function getParent();

    /**
     *
     * @param MediaInterface $parent            
     */
    public function setParent(MediaInterface $parent);
    
    /**
     * @return MediaInterface[]
     */
    public function getChildren();

    /**
     *
     * @param MediaInterface[] $children
     */
    public function setChildren(array $children);

    /**
     * @return string
     */
    public function getDownloadFilename(): string;
}

<?php
/**
 * HwmMedia Module
 *
 */
namespace Trollfjord\Bundle\MediaBaseBundle\Arranger;

use Trollfjord\Bundle\MediaBaseBundle\Storage\StorageInterface;
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DefaultArranger
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
class DefaultArranger implements ArrangerInterface
{

    /**
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * {@inheritDoc}
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * {@inheritDoc}
     */
    public function getResponse(Request $request, MediaInterface $media, $variation = null)
    {
        $respone = new Response();
        
        if (! $this->storage->exists($media, $variation)) {
            return null;
        }
        $content = $this->storage->read($media, $variation);

        $respone->headers->set("Content-Type", $media->getMimeType());
        $respone->headers->set("Content-Length", strlen($content));
        $respone->headers->set("Content-Disposition", 'inline; filename="'.$media->getDownloadFilename().'"');
        $respone->setContent($content);
        
        return $respone;
    }
    
    public function getThumbnailResponse(Request $request, MediaInterface $media, $variation = null)
    {
        // @todo Auslagern und splitten: Imagick und GD
        if($variation !== null && preg_match("/^preview_[0-9]+?x[0-9]+?$/Uis", $variation) && !$this->getStorage()->exists($media, $variation)) {
            $size = ltrim($variation, 'preview_');
            $tempFile = tempnam(sys_get_temp_dir(), 'tmp_');
            file_put_contents($tempFile, $this->getStorage()->read($media, 'preview'));
            passthru("convert ".escapeshellarg($tempFile)." +profile '!exif,*' -resize ".escapeshellarg($size)." ".escapeshellarg($tempFile));
            $resizedImage = file_get_contents($tempFile);
            $this->getStorage()->write($resizedImage, $media, $variation);
            unlink($tempFile);
        }
        return self::getResponse($request, $media, $variation);
    }
}
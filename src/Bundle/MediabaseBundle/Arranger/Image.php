<?php
/**
 * HwmMedia Module
 *
 * @link http://git.helliwood.de/ccbuchner/digitales_portfolio
 */
namespace Trollfjord\Bundle\MediaBaseBundle\Arranger;

use Trollfjord\Bundle\MediaBaseBundle\Storage\StorageInterface;
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Image
 *
 * @author Maurice Karg <karg@helliwood.com>
 */
class Image extends DefaultArranger
{
    public function getResponse(Request $request, MediaInterface $media, $variation = null)
    {
        if($media->getExtension()=="svg"){
            return  parent::getResponse($request, $media, null);
        }
        
        // @todo Auslagern und splitten: Imagick und GD
        if($variation !== null && !$this->getStorage()->exists($media, $variation)) {
            if(preg_match("/^[0-9]+?x[0-9]+?$/Uis", $variation)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'tmp_');
                file_put_contents($tempFile, $this->getStorage()->read($media));
                passthru("convert ".escapeshellarg($tempFile)." +profile '!exif,*' -auto-orient -resize ".escapeshellarg($variation)." ".escapeshellarg($tempFile));
                $resizedImage = file_get_contents($tempFile);
                $this->getStorage()->writeData($resizedImage, $media, $variation);
                unlink($tempFile);
            }
        }
        $response = parent::getResponse($request, $media, $variation);

        try {
            if (false !== ($mtime = $this->getStorage()->getModificationTime($media, $variation))) {
                $etag = md5($mtime.$media->getId().$variation);
                $modifiedSince = $request->headers->get('If-Modified-Since');
                $etagHeader = $request->headers->get('If-None-Match');
                if ($modifiedSince && false !== $modifiedSince || $etagHeader && false !== $etagHeader) {
                    if (strtotime($modifiedSince) == $mtime || ($etagHeader == $etag)) {
                        $response->setStatusCode(304);
                        $response->headers->set('Pragma', 'private');
                        $response->headers->set('Cache-Control', 'private');
                        return $response;
                    }
                }
                $response->headers->set('Pragma', 'private');
                $response->headers->set('Cache-Control', 'private');
                $response->headers->set('Etag', $etag);
                $response->headers->set('Last-Modified', date('Y-m-d H:i:s', $mtime));
                $response->headers->set('Expires', date('Y-m-d H:i:s', time()+(3600*24*30)));
            }
        } catch( \Exception $e ) {
            // try/catch für invalid date request from IE
        }
        
        return $response;
    }
}

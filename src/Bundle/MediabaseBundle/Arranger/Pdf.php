<?php
/**
 * HwmMedia Module
 *
 * @link http://git.helliwood.de/ccbuchner/digitales_portfolio
 */
namespace Trollfjord\Bundle\MediaBaseBundle\Arranger;

/*
use Zend\Http\Headers;
use Zend\Http\Header\ContentType;
use Zend\Http\Header\ContentLength;
*/
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pdf
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
class Pdf extends DefaultArranger
{
    public function getResponse(Request $request, MediaInterface $media, $variation = null)
    {
        // @todo Auslagern und splitten: Imagick und GD
        if($variation !== null && !$this->getStorage()->exists($media, $variation)) {
            if(preg_match("/^[0-9]+_[0-9]+?x[0-9]+?$/Uis", $variation)) {
                $_variation = explode("_", $variation);
                $tempFile = tempnam(sys_get_temp_dir(), 'tmp_');
                file_put_contents($tempFile, $this->getStorage()->read($media));
                passthru("gs -dBATCH -dNOPAUSE -sDEVICE=jpeg -r144 -dFirstPage=".escapeshellarg($_variation[0])." -dLastPage=".escapeshellarg($_variation[0])." -sOutputFile=".escapeshellarg($tempFile)." ".escapeshellarg($tempFile)." > /dev/null");
                passthru("convert ".escapeshellarg($tempFile)." -resize ".escapeshellarg($_variation[1])." ".escapeshellarg($tempFile));
                $resizedImage = file_get_contents($tempFile);
                $this->getStorage()->write($resizedImage, $media, $variation);
                unlink($tempFile);
                return $this->getPDfImageResponse($request, $media, $variation);
            }
        }
        elseif($variation !== null) {
            return $this->getPDfImageResponse($request, $media, $variation);
        }
        
        return parent::getResponse($request, $media, $variation);
    }
    
    public function getPDfImageResponse(Request $request, MediaInterface $media, $variation = null)
    {
        $response = new Response();
        $headers = new Headers();
        
        if (! $this->storage->exists($media, $variation)) {
            return null;
        }
        $content = $this->storage->read($media, $variation);
        $headers->addHeader(new ContentType("image/jpeg"));
        $headers->addHeader(new ContentLength(strlen($content)));
        
        $response->setHeaders($headers);
        $response->setContent($content);
        
        try {
            if (false !== ($mtime = $this->getStorage()->getModificationTime($media, $variation))) {
                $etag = md5($mtime.$media->getId().$variation);
                $modifiedSince = $request->getHeader('If-Modified-Since');
                $etagHeader = $request->getHeader('If-None-Match');
                if (false !== $modifiedSince || false !== $etagHeader) {
                    if (strtotime($modifiedSince->getDate()) == $mtime || ($etagHeader && $etagHeader->getFieldValue() == $etag)) {
                        $response->setStatusCode(304);
                    }
                }
                $response->getHeaders()->addHeaderLine('Pragma', 'private');
                $response->getHeaders()->addHeaderLine('Cache-Control', 'private');
                $response->getHeaders()->addHeaderLine('Etag', $etag);
                $response->getHeaders()->addHeaderLine('Last-Modified', date('Y-m-d H:i:s', $mtime));
                $response->getHeaders()->addHeaderLine('Expires', date('Y-m-d H:i:s', time()+(3600*24*30)));
            }
        } catch( \Exception $e ) {
            // try/catch für invalid date request from IE
        }
        
        return $response;
    }
}
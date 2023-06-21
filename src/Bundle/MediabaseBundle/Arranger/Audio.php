<?php
/**
 * HwmMedia Module
 *
 * @link http://git.helliwood.de/ccbuchner/digitales_portfolio
 */
namespace Trollfjord\Bundle\MediaBaseBundle\Arranger;

use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;
/**
 * Image
 *
 * @author Maurice Karg <karg@helliwood.com>
 */
class Audio extends DefaultArranger
{
    public function getResponse(Request $request, MediaInterface $media, $variation = null)
    {
        $response = parent::getResponse($request, $media, $variation);
        $response->getHeaders()->addHeader(new \Zend\Http\Header\AcceptRanges("bytes"));
        
        if($response->getHeaders()->has('Range')) {
            $_response = new \Zend\Http\Response\Stream();
            $_response->setHeaders($response->getHeaders());
            $_response->setStream(fopen($this->getStorage()->getFilepath($media), 'r'));
            return $_response;
        }
        
        return $response;
    }
}
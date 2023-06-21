<?php

namespace Trollfjord\Bundle\MediaBaseBundle\ControllerPublic;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\MediaBaseBundle\Service\MediaService;
use Trollfjord\Core\Controller\AbstractPublicController;

/**
 * Class ShowController
 * @package Trollfjord\Bundle\MediaBaseBundle\Controller
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @Route("/MediaBasePublic", name="media_base_public_")
 */
class IndexController extends AbstractPublicController
{

    /**
     * @Route("/show/{id<\d+>?}/{variation?}",
     *     name="show",
     *     defaults={"id": null, "variation": null},
     *     requirements={"variation": "[0-9]+x[0-9]+"}
     * )
     *
     * @return Response
     */
    public function indexAction(Request $request, MediaService $mediaService)
    {
        $mediaId = $request->attributes->get("id");
        $variation = $request->attributes->get("variation");
        $media = $mediaService->getMediaById($mediaId);
        if (! $media) {
            throw $this->createNotFoundException('Media not found!');
        }
        $arranger = $mediaService->getArrangerByMimeType($media->getMimeType());
        if (! $arranger) {
            throw $this->createNotFoundException('Arranger not found!');
        }
        if (strpos($variation, 'preview') === 0) {
            $response = $arranger->getThumbnailResponse($request, $media, $variation);
        } else {
            $response = $arranger->getResponse($request, $media, $variation);
        }
        if (! $response) {
            throw $this->createNotFoundException('Arranger Response not found!');
        }
        
        return $response;
    }
}

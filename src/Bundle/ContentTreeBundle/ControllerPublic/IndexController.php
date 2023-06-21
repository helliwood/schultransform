<?php

namespace Trollfjord\Bundle\ContentTreeBundle\ControllerPublic;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use Trollfjord\Core\Controller\AbstractPublicController;

/**
 * Class IndexController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\ContentTree\ControllerPublic
 *
 * @Route("/Public", name="content_tree_public_")
 */
class IndexController extends AbstractPublicController
{
    /**
     * @Route("/site", name="home")
     * @param Request     $request
     * @param SiteService $siteService
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function index(Request $request, SiteService $siteService): Response
    {
        $site = $siteService->getSiteByRoute($request->attributes->get('_route'));
        if ($site && $site->getPublishedSite() && $site->getPublishedSite()->isPublished()) {
            return $this->render('frontend/index/index.html.twig', [
                'site' => $site->getPublishedSite(),
                'siteContent' => $siteService->renderSiteContentsBySitePublished($site->getPublishedSite(), TemplateEngine::RENDER_MODE_PUBLIC)
            ]);
        }
        throw $this->createNotFoundException('Die Seite wurde nicht gefunden.');
    }
}

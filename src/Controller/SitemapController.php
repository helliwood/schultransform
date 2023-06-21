<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-09-04
 * Time: 6:30
 */

namespace Trollfjord\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Core\Controller\AbstractController;

class SitemapController extends AbstractController
{

    /**
     * @Route("/sitemap.xml", name="sitemap")
     * @param SiteService $siteService
     * @return Response
     */
    public function index(SiteService $siteService): Response
    {
        $sites = $siteService->getPublishedSites();

        $baseUrl = $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL);

        foreach ($sites as $site) {
            $tempray = [];
            if ($site->isPublished()) {
                //TODO: that looks wrong
                $url = rtrim($baseUrl, "/") . $site->getUrl();
                $tempray['url'] = $url;
                //TODO: need real lastmod here
                $tempray['lastmod'] = date("Y-m-d");
                $siteray[] = $tempray;

            }
        }

        $content = $this->renderView('frontend/index/sitemap.html.twig', [
            'sites' => $siteray
        ]);

        $textResponse = new Response($content, 200);
        $textResponse->headers->set('Content-Type', 'text/xml');
        return $textResponse;
    }
}

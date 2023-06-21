<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use Trollfjord\Core\Controller\AbstractController;
use function array_merge;
use function is_null;
use function is_numeric;
use function str_replace;

class AbstractSiteContentController extends AbstractController
{
    public const SITE_CONTENT_REPLACEMENT_STRING = "[#SUBCONTENT#]";
    /**
     * @var SiteService
     */
    protected SiteService $siteService;

    /**
     * @param SiteService $siteService
     */
    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    /**
     * @param string        $view
     * @param string[]      $parameters
     * @param Response|null $response
     * @return Response
     * @throws ElementNotFoundException|Exception
     */
    public function render(string $view, array $parameters = [], ?Response $response = null): Response
    {

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $siteId = Site::getIdByRouteName($request->attributes->get('_route'));
        if (is_numeric($siteId)) {
            $site = $this->siteService->getSite($siteId);
            if ((! is_null($site->getPublishedSite()) && $site->getPublishedSite()->isPublished())) {
                $content = parent::render($view, array_merge(["is_subcontent" => true], $parameters), $response);
                return parent::render('frontend/index/index.html.twig', [
                    'site' => $site,
                    'siteContent' => str_replace(self::SITE_CONTENT_REPLACEMENT_STRING, $content->getContent(), $this->siteService->renderSiteContents($site, TemplateEngine::RENDER_MODE_PUBLIC))
                ]);
            }
            throw $this->createNotFoundException();
        }
        return parent::render($view, $parameters, $response);
    }
}

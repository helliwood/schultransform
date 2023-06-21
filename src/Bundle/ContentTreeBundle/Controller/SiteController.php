<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Knp\Menu\MenuItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteHistory;
use Trollfjord\Bundle\ContentTreeBundle\Form\SiteType;
use Trollfjord\Bundle\ContentTreeBundle\Repository\SiteHistoryRepository;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception as TemplateEngineException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use Trollfjord\Core\Controller\AbstractController;
use function is_null;

/**
 * Class IndexController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\ContentTree
 *
 * @Route("/ContentTree/Site", name="content_tree_site_")
 */
class SiteController extends AbstractController
{

    /**
     * @Route("/preview/{id}", name="preview")
     * @param Site        $site
     * @param SiteService $siteService
     * @return Response
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function preview(Site $site, SiteService $siteService): Response
    {
        return $this->render('frontend/index/index.html.twig', [
            'site' => $site,
            'siteContent' => $siteService->renderSiteContents($site, TemplateEngine::RENDER_MODE_PUBLIC)
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param Site        $site
     * @param SiteService $siteService
     * @param MenuItem    $menu
     * @return Response
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function edit(Site $site, SiteService $siteService, MenuItem $menu): Response
    {
        $site->canBePublished();
        //for breadcrumb
        $menu['content_tree']->addChild('Seite bearbeiten: ' . $site->getName(), [
            'route' => 'content_tree_site_edit',
            'routeParameters' => ['id' => $site->getId()],
        ]);

        return $this->render('@ContentTree/site/edit.html.twig', [
            'site' => $site,
            'siteContent' => $siteService->renderSiteContents($site, TemplateEngine::RENDER_MODE_BACKEND)
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete")
     * @param Site        $site
     * @param SiteService $siteService
     * @return JsonResponse
     * @throws Exception
     */
    public function delete(Site $site, SiteService $siteService): JsonResponse
    {
        $siteService->deleteSite($site);
        return new JsonResponse('deleted');
    }

    /**
     * @Route("/undelete/{id}", name="undelete")
     * @param Site        $site
     * @param SiteService $siteService
     * @return JsonResponse
     * @throws Exception
     */
    public function undelete(Site $site, SiteService $siteService): JsonResponse
    {
        $siteService->undeleteSite($site);
        return new JsonResponse('undeleted');
    }

    /**
     * @Route("/add-site/{id}", defaults={"id"=null}, name="add_site_form")
     * @param Site|null   $duplicateSite
     * @param SiteService $siteService
     * @param Request     $request
     * @return Response
     * @throws Exception
     */
    public function addSiteForm(?Site $duplicateSite, SiteService $siteService, Request $request): Response
    {
        $site = new Site();
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);
        $finish = false;
        if ($form->isSubmitted() && $form->isValid()) {
            if (! is_null($duplicateSite)) {
                $siteContents = new ArrayCollection();
                foreach ($duplicateSite->getContentByParentNull() as $siteContent) {
                    $sc = clone $siteContent;
                    $sc->setSite($site);
                    $siteContents->add($sc);
                }
                $site->setContent($siteContents);
            }
            $siteService->addSite($site);
            $finish = true;
        }
        return $this->render('@ContentTree/site/add-site-form.html.twig', [
            'form' => $form->createView(),
            'duplicateSite' => $duplicateSite,
            'finish' => $finish
        ]);
    }

    /**
     * @Route("/metas/{id}", name="metas_form")
     * @param Site        $site
     * @param SiteService $siteService
     * @param Request     $request
     * @return Response
     * @throws Exception
     */
    public function metasForm(Site $site, SiteService $siteService, Request $request): Response
    {
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);
        $finish = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $siteService->saveSite($form->getData());
            $finish = true;
        }
        return $this->render('@ContentTree/site/metas-form.html.twig', [
            'form' => $form->createView(),
            'finish' => $finish,
            'site' => $site
        ]);
    }

    /**
     * @Route("/history/{id}", name="history")
     * @param Site    $site
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Site $site, Request $request): JsonResponse
    {
        /** @var SiteHistoryRepository $qr */
        $shr = $this->getDoctrine()->getRepository(SiteHistory::class);

        return new JsonResponse(
            $shr->find4Ajax(
                $site,
                $request->query->getAlnum('sort', 'date'),
                $request->query->getBoolean('sortDesc', true),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            )
        );
    }

    /**
     * @Route("/publish/{id}", name="publish")
     * @param Site        $site
     * @param SiteService $siteService
     * @return JsonResponse
     * @throws Exception
     */
    public function publish(Site $site, SiteService $siteService): JsonResponse
    {
        $siteService->publishSite($site);
        return new JsonResponse('published');
    }

    /**
     * @Route("/slugify", name="slugify", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @return JsonResponse
     */
    public function slugify(Request $request): JsonResponse
    {
        $slug = Site::slugify($request->query->get('name'));
        return new JsonResponse($slug ?? "");
    }
}

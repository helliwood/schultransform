<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Controller;

use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use Trollfjord\Bundle\ContentTreeBundle\Repository\SiteContentRepository;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\Service\SnippetService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception as TemplateEngineException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use Trollfjord\Core\Controller\AbstractController;
use function array_map;
use function explode;
use function is_null;
use function is_numeric;
use function str_replace;

/**
 * Class SnippetController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\ContentTree
 *
 * @Route("/ContentTree/Site/Site-Content", name="content_tree_site_sitecontent_")
 */
class SiteContentController extends AbstractController
{
    public const SESSION_NAMESPACE_SNIPPET_COPY = self::class . '_SNIPPET_COPY';

    /**
     * @Route("/snippets/{allowedGroups}", name="snippets")
     * @param SnippetService $snippetService
     * @param string         $allowedGroups
     * @return JsonResponse
     */
    public function snippets(SnippetService $snippetService, string $allowedGroups): JsonResponse
    {
        return new JsonResponse($snippetService->getSnippetsByAllowedGroups(array_map('trim', explode(',', $allowedGroups))));
    }

    /**
     * @Route("/form/{id}", name="form")
     * @param SiteContent $siteContent
     * @param SiteService $siteService
     * @param Request     $request
     * @return Response
     * @throws Exception
     */
    public function form(SiteContent $siteContent, SiteService $siteService, Request $request): Response
    {
        $form = $siteService->getForm($siteContent);
        $form->handleRequest($request);
        $dataChanged = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $siteService->saveSiteContentWithData($siteContent, $form->getData(), $request->request->all(TemplateEngine::SNIPPET_FORM_PREFIX));
            $action = $form->getData()["TE-action-TE"];
            if ($action !== null) {
                $siteService->modify($siteContent, $action, str_replace(TemplateEngine::SNIPPET_FORM_PREFIX, '', $form->getData()["TE-action_value-TE"]));
                $siteService->saveSiteContent($siteContent);
            }
            $dataChanged = true;
        }
        if ($dataChanged) {
            return $this->redirectToRoute('content_tree_site_sitecontent_form', ['id' => $siteContent->getId()]);
        }
        return $this->render('@ContentTree/site-content/form.html.twig', [
            'formThemeTemplates' => $siteService->getFormThemeTemplates(),
            'form' => $form->createView(),
            'siteContent' => $siteContent
        ]);
    }

    /**
     * @Route("/content/{id}", name="content")
     * @param SiteContent    $siteContent
     * @param TemplateEngine $templateEngine
     * @return Response
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function content(SiteContent $siteContent, TemplateEngine $templateEngine): Response
    {
        return $this->render('@ContentTree/site-content/content.html.twig', [
            'snippet_content' => $templateEngine->renderSiteContent($siteContent, TemplateEngine::RENDER_MODE_BACKEND, false)
        ]);
    }

    /**
     * @Route("/content-area/{site}/{siteContent}/{area}", name="content_area", defaults={"siteContent"=null, "area"=null})
     * @ParamConverter("site", options={"mapping": {"site": "id"}})
     * @ParamConverter("siteContent", options={"mapping": {"siteContent": "id"}})
     * @param Site             $site
     * @param SiteContent|null $siteContent
     * @param string|null      $area
     * @param SiteService      $siteService
     * @param Request          $request
     * @return Response
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function contentArea(Site $site, ?SiteContent $siteContent, ?string $area, SiteService $siteService, Request $request): Response
    {
        // SiteContent-Id gesetzt aber Entity nicht vorhanden
        if (is_null($siteContent) && is_numeric($request->attributes->get('_route_params')['siteContent'])) {
            $content = '';
        } else {
            $content = $siteService->renderSiteContentsArea($site, $siteContent, $area, TemplateEngine::RENDER_MODE_BACKEND);
        }
        return $this->render('@ContentTree/site-content/content_area.html.twig', [
            'snippet_content' => $content
        ]);
    }

    /**
     * @Route("/add/{site}/{siteContent}/{area}", name="add", defaults={"siteContent"=null, "area"=null})
     * @ParamConverter("site", options={"mapping": {"site": "id"}})
     * @ParamConverter("siteContent", options={"mapping": {"siteContent": "id"}})
     * @param SiteService      $siteService
     * @param Request          $request
     * @param Site             $site
     * @param SiteContent|null $siteContent
     * @param string|null      $area
     * @return JsonResponse
     * @throws Exception
     */
    public function add(SiteService $siteService, Request $request, Site $site, ?SiteContent $siteContent = null, ?string $area = null): JsonResponse
    {
        try {
            $newSiteContent = $siteService->addSiteContent($request->request->get('snippetId'), $site, ! empty($request->request->get('after')) ? $request->request->getInt('after') : null, $siteContent, $area);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
        return new JsonResponse($newSiteContent);
    }

    /**
     * @Route("/delete/{id}", name="delete")
     * @param SiteContent|null $siteContent
     * @param SiteService      $siteService
     * @return JsonResponse
     */
    public function delete(SiteContent $siteContent, SiteService $siteService): JsonResponse
    {
        try {
            $siteService->deleteSiteContent($siteContent);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
        return new JsonResponse(["deleted"]);
    }

    /**
     * @Route("/up/{id}", name="up")
     * @param SiteService      $siteService
     * @param SiteContent|null $siteContent
     * @return JsonResponse
     */
    public function up(SiteService $siteService, SiteContent $siteContent): JsonResponse
    {
        try {
            $siteService->up($siteContent);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
        return new JsonResponse(["up"]);
    }

    /**
     * @Route("/down/{id}", name="down")
     * @param SiteService      $siteService
     * @param SiteContent|null $siteContent
     * @return JsonResponse
     */
    public function down(SiteService $siteService, SiteContent $siteContent): JsonResponse
    {
        try {
            $siteService->down($siteContent);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
        return new JsonResponse(["down"]);
    }

    /**
     * @Route("/copy/{id<\d+>}", name="copy")
     * @param int              $id
     * @param SessionInterface $session
     * @return JsonResponse
     * @throws Exception
     */
    public function copy(int $id, SessionInterface $session): JsonResponse
    {
        try {
            $session->set(self::SESSION_NAMESPACE_SNIPPET_COPY, $id);
            /** @var SiteContentRepository $scr */
            $scr = $this->getDoctrine()->getRepository(SiteContent::class);
            $siteContent = $scr->find($id);
            if (! $siteContent) {
                throw new Exception('SiteContent ' . $id . ' not found!');
            }
            return new JsonResponse($siteContent);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/paste/{site}/{siteContent}/{area}", name="paste", defaults={"siteContent"=null, "area"=null})
     * @ParamConverter("site", options={"mapping": {"site": "id"}})
     * @ParamConverter("siteContent", options={"mapping": {"siteContent": "id"}})
     * @param SiteService      $siteService
     * @param Request          $request
     * @param Site             $site
     * @param SiteContent|null $siteContent
     * @param string|null      $area
     * @return JsonResponse
     */
    public function paste(SiteService $siteService, Request $request, Site $site, ?SiteContent $siteContent = null, ?string $area = null): JsonResponse
    {
        try {
            $siteService->pasteSiteContent($request->request->getInt('siteContentCopyId'), $site, $siteContent, $area);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
        return new JsonResponse(["pasted"]);
    }
}

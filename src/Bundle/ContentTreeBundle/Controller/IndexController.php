<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Knp\Menu\MenuItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Snippet;
use Trollfjord\Bundle\ContentTreeBundle\Form\SnippetType;
use Trollfjord\Bundle\ContentTreeBundle\Repository\SnippetRepository;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Core\Controller\AbstractController;

/**
 * Class IndexController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\ContentTree
 *
 * @Route("/ContentTree", name="content_tree_")
 */
class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('@ContentTree/index/index.html.twig', [

        ]);
    }

    /**
     * @Route("/tree", name="tree", condition="request.isXmlHttpRequest()")
     * @param SiteService $siteService
     * @return JsonResponse
     */
    public function tree(SiteService $siteService): JsonResponse
    {
        return new JsonResponse($siteService->getContentTree());
    }

    /**
     * @Route("/snippets", name="snippets")
     * @param Request  $request
     * @param MenuItem $menu
     * @return JsonResponse|Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function snippets(Request $request, MenuItem $menu)
    {
        $menu['content_tree']->addChild('Snippets', [
            'route' => 'content_tree_snippets'
        ]);

        if ($request->isXmlHttpRequest()) {
            /** @var SnippetRepository $cr */
            $sr = $this->getDoctrine()->getRepository(Snippet::class);
            return new JsonResponse($sr->find4Ajax(
                $request->query->getAlnum('sort', 'position'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        return $this->render('@ContentTree/index/snippets.html.twig', [
        ]);
    }

    /**
     * @Route("/edit-snippet/{id}", name="edit_snippet")
     * @param Snippet  $snippet
     * @param Request  $request
     * @param MenuItem $menu
     * @return RedirectResponse|Response
     */
    public function editSnippet(Snippet $snippet, Request $request, MenuItem $menu)
    {
        $menu['content_tree']->addChild('Snippet bearbeiten', [
            'route' => 'content_tree_edit_snippet',
            'routeParameters' => ['id' => $snippet->getId()],
        ]);

        $form = $this->createForm(SnippetType::class, $snippet, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($snippet);
            $em->flush($snippet);
            $this->addFlash(
                'success',
                'Snippet erfolgreich gespeichert!'
            );
            return $this->redirectToRoute('content_tree_snippets');
        }

        return $this->render('@ContentTree/index/edit_snippet.html.twig', [
            'form' => $form->createView()
        ]);
    }
}

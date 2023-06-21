<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Knp\Menu\MenuItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Form\RecommendationType;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\RecommendationRepository;
use Trollfjord\Core\Controller\AbstractController;

/**
 * Class IndexController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Controller
 *
 * @Route("/Questionnaire_Recommendation", name="questionnaire_recommendation_")
 */
class RecommendationController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @param Request $request
     * @return Response|JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function index(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var RecommendationRepository $rr */
            $rr = $this->getDoctrine()->getRepository(Recommendation::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "delete_recommendation":
                        $r = $rr->find($request->get('recommendation_id', null));
                        $em->remove($r);
                        $em->flush();
                        break;
                }
            }
            return new JsonResponse($rr->find4Ajax(
                $request->query->getAlnum('sort', 'position'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        return $this->render('@Questionnaire/recommendation/index.html.twig', [
        ]);
    }

    /**
     * @Route("/add", name="add")
     * @param Request  $request
     * @param MenuItem $menu
     * @return Response
     */
    public function add(Request $request, MenuItem $menu): Response
    {
        $menu['questionnaire']['recommendation']->addChild('Neuer Datensatz', ['route' => 'questionnaire_recommendation_add']);
        $recommendation = new Recommendation();
        $form = $this->createForm(RecommendationType::class, $recommendation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $recommendation->setCreatedBy($this->getUser());
            $this->getDoctrine()->getManager()->persist($recommendation);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Der Datensatz wurde erfolgreich gespeichert!');
            return $this->redirectToRoute('questionnaire_recommendation_home');
        }
        return $this->render('@Questionnaire/recommendation/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param Recommendation $recommendation
     * @param Request        $request
     * @param MenuItem       $menu
     * @return Response
     */
    public function edit(Recommendation $recommendation, Request $request, MenuItem $menu): Response
    {
        $menu['questionnaire']['recommendation']->addChild('Datensatz bearbeiten', [
            'route' => 'questionnaire_recommendation_edit',
            'routeParameters' => ['id' => $recommendation->getId()]
        ]);
        $form = $this->createForm(RecommendationType::class, $recommendation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($recommendation);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Der Datensatz wurde erfolgreich gespeichert!');
            return $this->redirectToRoute('questionnaire_recommendation_home');
        }
        return $this->render('@Questionnaire/recommendation/edit.html.twig', [
            'form' => $form->createView(),
            'recommendation' => $recommendation
        ]);
    }

    /**
     * @Route("/show/{id}", name="show")
     * @param Recommendation $recommendation
     * @param Request        $request
     * @param MenuItem       $menu
     * @return Response
     */
    public function show(Recommendation $recommendation, Request $request, MenuItem $menu): Response
    {
        $menu['questionnaire']['recommendation']->addChild('Datensatz anzeigen', [
            'route' => 'questionnaire_recommendation_show',
            'routeParameters' => ['id' => $recommendation->getId()]
        ]);
        return $this->render('@Questionnaire/recommendation/show.html.twig', [
            'recommendation' => $recommendation
        ]);
    }
}

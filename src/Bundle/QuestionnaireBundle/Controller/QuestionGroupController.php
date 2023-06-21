<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionGroup;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Form\QuestionGroupType;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionGroupRepository;
use Trollfjord\Core\Controller\AbstractController;

/**
 * Class QuestionGroupController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Controller
 *
 * @Route("/Questionnaire/QuestionGroup", name="questionnaire_questiongroup_")
 */
class QuestionGroupController extends AbstractController
{
    /**
     * @Route("/{id}", name="home", defaults={"parent"=null})
     * @ParamConverter("parent", options={"mapping": {"parent": "id"}})
     * @param Questionnaire $questionnaire
     * @param Request       $request
     * @param MenuItem      $menu
     * @return Response|JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function index(Questionnaire $questionnaire, Request $request, MenuItem $menu)
    {
        $menu['questionnaire']->addChild($questionnaire->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionnaire->getCategory()->getId()],
        ])->addChild($questionnaire->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionnaire->getId()],
        ]);

        if ($request->isXmlHttpRequest()) {
            /** @var QuestionGroupRepository $qgr */
            $qgr = $this->getDoctrine()->getRepository(QuestionGroup::class);
            return new JsonResponse($qgr->find4Ajax(
                $questionnaire,
                $request->query->getAlnum('sort', 'position'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        return $this->render('@Questionnaire/question-group/index.html.twig', [
            'questionnaire' => $questionnaire
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param QuestionGroup $questionGroup
     * @param Request       $request
     * @param MenuItem      $menu
     * @return RedirectResponse|Response
     */
    public function edit(QuestionGroup $questionGroup, Request $request, MenuItem $menu)
    {
        $questionnaire = $questionGroup->getQuestionnaire();
        $menu['questionnaire']->addChild($questionnaire->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionnaire->getCategory()->getId()],
        ])->addChild($questionnaire->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionnaire->getId()],
        ])->addChild($questionGroup->getName() . ' Bearbeiten', [
            'route' => 'questionnaire_questiongroup_edit',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ]);

        $form = $this->createForm(QuestionGroupType::class, $questionGroup);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($questionGroup);
            $em->flush();
            $this->addFlash(
                'success',
                'Fragegruppe erfolgreich gespeichert'
            );
            return $this->redirectToRoute('questionnaire_questiongroup_home', ['id' => $questionnaire->getId()]);
        }

        return $this->render('@Questionnaire/question-group/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }
}

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
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionChoice;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionGroup;
use Trollfjord\Bundle\QuestionnaireBundle\Form\QuestionChoiceType;
use Trollfjord\Bundle\QuestionnaireBundle\Form\QuestionType;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionRepository;
use Trollfjord\Core\Controller\AbstractController;
use function is_null;
use function substr;

/**
 * Class QuestionController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Controller
 *
 * @Route("/Questionnaire/QuestionGroup/Question", name="questionnaire_question_")
 */
class QuestionController extends AbstractController
{
    /**
     * @Route("/{id}", name="home", defaults={"parent"=null})
     * @ParamConverter("parent", options={"mapping": {"parent": "id"}})
     * @param QuestionGroup $questionGroup
     * @param Request       $request
     * @param MenuItem      $menu
     * @return Response|JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function index(QuestionGroup $questionGroup, Request $request, MenuItem $menu)
    {
        $menu['questionnaire']->addChild($questionGroup->getQuestionnaire()->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionGroup->getQuestionnaire()->getCategory()->getId()],
        ])->addChild($questionGroup->getQuestionnaire()->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionGroup->getQuestionnaire()->getId()],
        ])->addChild($questionGroup->getName(), [
            'route' => 'questionnaire_question_home',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ]);

        if ($request->isXmlHttpRequest()) {
            /** @var QuestionRepository $qr */
            $qr = $this->getDoctrine()->getRepository(Question::class);
            return new JsonResponse($qr->find4Ajax(
                $questionGroup,
                $request->query->getAlnum('sort', 'position'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        return $this->render('@Questionnaire/question/index.html.twig', [
            'questionGroup' => $questionGroup
        ]);
    }

    /**
     * @Route("/add/{id}", name="add")
     * @param QuestionGroup $questionGroup
     * @param Request       $request
     * @param MenuItem      $menu
     * @return RedirectResponse|Response
     */
    public function addQuestion(QuestionGroup $questionGroup, Request $request, MenuItem $menu)
    {
        $menu['questionnaire']->addChild($questionGroup->getQuestionnaire()->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionGroup->getQuestionnaire()->getCategory()->getId()],
        ])->addChild($questionGroup->getQuestionnaire()->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionGroup->getQuestionnaire()->getId()],
        ])->addChild($questionGroup->getName(), [
            'route' => 'questionnaire_question_home',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ])->addChild('Frage hinzufügen', [
            'route' => 'questionnaire_question_add',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ]);
        $question = new Question();
        $question->setQuestionGroup($questionGroup);
        $question->setPosition($questionGroup->getQuestions()->count() + 1);

        $form = $this->createForm(QuestionType::class, $question, ['new' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();
            $this->addFlash(
                'success',
                'Frage erfolgreich gespeichert'
            );
            return $this->redirectToRoute('questionnaire_question_home', ['id' => $question->getQuestionGroup()->getId()]);
        }

        return $this->render('@Questionnaire/question/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param Question $question
     * @param Request  $request
     * @param MenuItem $menu
     * @return RedirectResponse|Response
     */
    public function edit(Question $question, Request $request, MenuItem $menu)
    {

        $questionGroup = $question->getQuestionGroup();
        $menu['questionnaire']->addChild($questionGroup->getQuestionnaire()->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionGroup->getQuestionnaire()->getCategory()->getId()],
        ])->addChild($questionGroup->getQuestionnaire()->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionGroup->getQuestionnaire()->getId()],
        ])->addChild($questionGroup->getName(), [
            'route' => 'questionnaire_question_home',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ])->addChild('Frage Bearbeiten', [
            'route' => 'questionnaire_question_edit',
            'routeParameters' => ['id' => $question->getId()],
        ]);

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();
            $this->addFlash(
                'success',
                'Frage erfolgreich gespeichert'
            );
            return $this->redirectToRoute('questionnaire_question_home', ['id' => $question->getQuestionGroup()->getId()]);
        }

        return $this->render('@Questionnaire/question/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/{id}/choices", name="choices")
     * @param Question $question
     * @param MenuItem $menu
     * @return Response
     */
    public function choices(Question $question, MenuItem $menu): Response
    {
        $questionGroup = $question->getQuestionGroup();
        $menu['questionnaire']->addChild($questionGroup->getQuestionnaire()->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionGroup->getQuestionnaire()->getCategory()->getId()],
        ])->addChild($questionGroup->getQuestionnaire()->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionGroup->getQuestionnaire()->getId()],
        ])->addChild($questionGroup->getName(), [
            'route' => 'questionnaire_question_home',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ])->addChild('Frage: ' . substr($question->getQuestion(), 0, 20) . '...', [
            'route' => 'questionnaire_question_edit',
            'routeParameters' => ['id' => $question->getId()],
        ])->addChild('Antworten', [
            'route' => 'questionnaire_question_choices',
            'routeParameters' => ['id' => $question->getId()],
        ]);

        return $this->render('@Questionnaire/question/choices.html.twig', [
            'question' => $question
        ]);
    }

    /**
     * @Route("/{id}/add-choice", name="add_choice")
     * @param Question $question
     * @param Request  $request
     * @param MenuItem $menu
     * @return Response
     */
    public function addChoice(Question $question, Request $request, MenuItem $menu): Response
    {
        $questionGroup = $question->getQuestionGroup();
        $menu['questionnaire']->addChild($questionGroup->getQuestionnaire()->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionGroup->getQuestionnaire()->getCategory()->getId()],
        ])->addChild($questionGroup->getQuestionnaire()->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionGroup->getQuestionnaire()->getId()],
        ])->addChild($questionGroup->getName(), [
            'route' => 'questionnaire_question_home',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ])->addChild('Frage: ' . substr($question->getQuestion(), 0, 20) . '...', [
            'route' => 'questionnaire_question_edit',
            'routeParameters' => ['id' => $question->getId()],
        ])->addChild('Antworten', [
            'route' => 'questionnaire_question_choices',
            'routeParameters' => ['id' => $question->getId()],
        ])->addChild('Antwort Hinzufügen', [
            'route' => 'questionnaire_question_add_choice',
            'routeParameters' => ['id' => $question->getId()],
        ]);
        $choice = new QuestionChoice();
        $form = $this->createForm(QuestionChoiceType::class, $choice, ['school_types' => $question->getSchoolTypes()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $choice->setQuestion($question);
            $choice->setPosition($question->getChoices()->count() + 1);
            $em->persist($choice);
            $em->flush();
            $this->addFlash(
                'success',
                'Antwort erfolgreich gespeichert'
            );
            return $this->redirectToRoute('questionnaire_question_choices', ['id' => $question->getId()]);
        }
        return $this->render('@Questionnaire/question/add_choice.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit-choice/{id}", name="edit_choice")
     * @param QuestionChoice $choice
     * @param Request        $request
     * @param MenuItem       $menu
     * @return Response
     */
    public function editChoice(QuestionChoice $choice, Request $request, MenuItem $menu): Response
    {
        $questionGroup = $choice->getQuestion()->getQuestionGroup();
        $menu['questionnaire']->addChild($questionGroup->getQuestionnaire()->getCategory()->getName(), [
            'route' => 'questionnaire_home',
            'routeParameters' => ['parent' => $questionGroup->getQuestionnaire()->getCategory()->getId()],
        ])->addChild($questionGroup->getQuestionnaire()->getName(), [
            'route' => 'questionnaire_questiongroup_home',
            'routeParameters' => ['id' => $questionGroup->getQuestionnaire()->getId()],
        ])->addChild($questionGroup->getName(), [
            'route' => 'questionnaire_question_home',
            'routeParameters' => ['id' => $questionGroup->getId()],
        ])->addChild('Frage: ' . substr($choice->getQuestion()->getQuestion(), 0, 20) . '...', [
            'route' => 'questionnaire_question_edit',
            'routeParameters' => ['id' => $choice->getQuestion()->getId()],
        ])->addChild('Antworten', [
            'route' => 'questionnaire_question_choices',
            'routeParameters' => ['id' => $choice->getQuestion()->getId()],
        ])->addChild('Antwort Bearbeiten', [
            'route' => 'questionnaire_question_edit_choice',
            'routeParameters' => ['id' => $choice->getId()],
        ]);
        $form = $this->createForm(QuestionChoiceType::class, $choice, ['school_types' => $choice->getQuestion()->getSchoolTypes()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($choice);
            $em->flush();
            $this->addFlash(
                'success',
                'Antwort erfolgreich gespeichert'
            );
            return $this->redirectToRoute('questionnaire_question_choices', ['id' => $choice->getQuestion()->getId()]);
        }
        return $this->render('@Questionnaire/question/edit_choice.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/choice-up/{id}", name="choice_up")
     * @param QuestionChoice $choice
     * @return RedirectResponse
     */
    public function choiceUp(QuestionChoice $choice): RedirectResponse
    {
        if ($choice->getPosition() > 1) {
            $otherChoice = $this->getDoctrine()->getRepository(QuestionChoice::class)->findOneBy(['question' => $choice->getQuestion(), 'position' => $choice->getPosition() - 1]);
            if (! is_null($otherChoice)) {
                $otherChoice->setPosition($otherChoice->getPosition() + 1);
                $choice->setPosition($choice->getPosition() - 1);
                $this->getDoctrine()->getManager()->flush();
            }
        }
        return $this->redirectToRoute('questionnaire_question_choices', ['id' => $choice->getQuestion()->getId()]);
    }

    /**
     * @Route(path="/choice-down/{id}", name="choice_down")
     * @param QuestionChoice $choice
     * @return RedirectResponse
     */
    public function choiceDown(QuestionChoice $choice): RedirectResponse
    {
        if ($choice->getPosition() < $choice->getQuestion()->getChoices()->count()) {
            $otherChoice = $this->getDoctrine()->getRepository(QuestionChoice::class)->findOneBy(['question' => $choice->getQuestion(), 'position' => $choice->getPosition() + 1]);
            if (! is_null($otherChoice)) {
                $otherChoice->setPosition($otherChoice->getPosition() - 1);
                $choice->setPosition($choice->getPosition() + 1);
                $this->getDoctrine()->getManager()->flush();
            }
        }
        return $this->redirectToRoute('questionnaire_question_choices', ['id' => $choice->getQuestion()->getId()]);
    }

    /**
     * @Route(path="/delete-choice/{id}", name="delete_choice")
     * @param QuestionChoice $choice
     * @return RedirectResponse
     */
    public function deleteChoice(QuestionChoice $choice): RedirectResponse
    {
        $choice->getQuestion()->getChoices()->removeElement($choice);
        $choice->getQuestion()->reorderChoices();
        $this->getDoctrine()->getManager()->remove($choice);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('questionnaire_question_choices', ['id' => $choice->getQuestion()->getId()]);
    }
}

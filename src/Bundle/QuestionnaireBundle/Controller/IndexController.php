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
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Form\CategoryType;
use Trollfjord\Bundle\QuestionnaireBundle\Form\QuestionnaireType;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\CategoryRepository;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionnaireRepository;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Entity\SchoolType;

/**
 * Class IndexController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Controller
 *
 * @Route("/Questionnaire", name="questionnaire_")
 */
class IndexController extends AbstractController
{
    /**
     * @Route("/{parent}", name="home", defaults={"parent"=null})
     * @ParamConverter("parent", options={"mapping": {"parent": "id"}})
     * @param Category|null $parent
     * @param Request       $request
     * @param MenuItem      $menu
     * @return Response|JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function index(?Category $parent, Request $request, MenuItem $menu)
    {
        $this->addCategories2Menu($menu, $parent);

        if ($request->isXmlHttpRequest()) {
            /** @var CategoryRepository $cr */
            $cr = $this->getDoctrine()->getRepository(Category::class);
            return new JsonResponse($cr->find4Ajax(
                $request->query->getAlnum('sort', 'position'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1),
                $parent
            ));
        }

        return $this->render('@Questionnaire/index/index.html.twig', [
            'category' => $parent
        ]);
    }

    /**
     * @Route("/questionnaires/{id}", name="questionnaires")
     * @param Category $category
     * @param Request  $request
     * @return Response|JsonResponse
     * @throws NonUniqueResultException|NoResultException
     */
    public function questionnaires(Category $category, Request $request)
    {
        /** @var QuestionnaireRepository $qr */
        $qr = $this->getDoctrine()->getRepository(Questionnaire::class);
        return new JsonResponse($qr->find4Ajax(
            $category,
            $request->query->getAlnum('sort', 'position'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
    }

    /**
     * @Route("/show-questionnaire/{id}", name="show_questionnaire")
     * @param Questionnaire $questionnaire
     * @param MenuItem      $menu
     * @return Response
     */
    public function showQuestionnaire(Questionnaire $questionnaire, Request $request, MenuItem $menu): Response
    {
        $this->addCategories2Menu($menu, $questionnaire->getCategory())->addChild($questionnaire->getName(), [
            'route' => 'questionnaire_show_questionnaire',
            'routeParameters' => ['id' => $questionnaire->getId()],
        ]);
        $schoolTypes = $this->getDoctrine()->getRepository(SchoolType::class)->findAll();
        $schoolType = $this->getDoctrine()->getRepository(SchoolType::class)->findOneBy(['name' => $request->query->get('schoolType', 'weiterführende Schule')]);
        $questionnaire->setCurrentSchoolType($schoolType);
        return $this->render('@Questionnaire/index/show_questionnaire.html.twig', [
            'questionnaire' => $questionnaire,
            'schoolTypes' => $schoolTypes,
            'schoolType' => $schoolType
        ]);
    }


    /**
     * @Route("/edit-questionnaire/{id}", name="edit_questionnaire")
     * @param Questionnaire $questionnaire
     * @param Request       $request
     * @param SiteService   $siteService
     * @return RedirectResponse|Response
     */
    public function editQuestionnaire(Questionnaire $questionnaire, Request $request, SiteService $siteService)
    {
        $form = $this->createForm(QuestionnaireType::class, $questionnaire, ['site_service' => $siteService]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($questionnaire);
            $em->flush($questionnaire);
            $this->addFlash(
                'success',
                'Kategorie erfolgreich angelegt'
            );
            return $this->redirectToRoute('questionnaire_home', ['parent' => $questionnaire->getCategory()->getId()]);
        }

        return $this->render('@Questionnaire/index/edit_questionnaire.html.twig', [
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/add-category/{parent}", name="add_category", defaults={"parent"=null})
     * @ParamConverter("parent", options={"mapping": {"parent": "id"}})
     * @param Category|null $parent
     * @param Request       $request
     * @param MenuItem      $menu
     * @return RedirectResponse|Response
     */
    public function addCategory(?Category $parent, Request $request, MenuItem $menu)
    {
        $this->addCategories2Menu($menu, $parent)->addChild('Neue Kategorie', [
            'route' => 'questionnaire_add_category',
            'routeParameters' => ['parent' => $parent ? $parent->getId() : 'null'],
        ])->setCurrent(true);
        $mainCategories = $this->getDoctrine()->getRepository(Category::class)->findAll();
        $category = new Category();
        $category->setParent($parent);
        $category->setPosition($parent ? $parent->getChildren()->count() + 1 : count($mainCategories) + 1);

        $form = $this->createForm(CategoryType::class, $category, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush($category);
            $this->addFlash(
                'success',
                'Kategorie erfolgreich angelegt'
            );
            return $this->redirectToRoute('questionnaire_home', [
                'parent' => $parent ? $parent->getId() : null
            ]);
        }

        return $this->render('@Questionnaire/index/new_category.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit-category/{id}", name="edit_category")
     * @param Category    $category
     * @param Request     $request
     * @param SiteService $siteService
     * @return RedirectResponse|Response
     */
    public function editCategory(Category $category, Request $request, SiteService $siteService)
    {


        $form = $this->createForm(CategoryType::class, $category, ['site_service' => $siteService]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush($category);
            $this->addFlash(
                'success',
                'Kategorie erfolgreich gespeichert'
            );
            return $this->redirectToRoute('questionnaire_home', ['parent' => $category->getParent() ? $category->getParent()->getId() : null]);
        }

        return $this->render('@Questionnaire/index/edit_category.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param MenuItem      $menu
     * @param Category|null $category
     * @return MenuItem
     */
    protected function addCategories2Menu(MenuItem $menu, ?Category $category): MenuItem
    {
        if (! is_null($category)) {
            $categories = [];
            $currentCategory = $category;
            while (! is_null($currentCategory)) {
                array_unshift($categories, $currentCategory);
                $currentCategory = $currentCategory->getParent();
            }
            $menu = $menu['questionnaire']->setCurrent(false);
            //for breadcrumb
            foreach ($categories as $category) {
                $menu = $menu->addChild($category->getName(), [
                    'route' => 'questionnaire_home',
                    'routeParameters' => ['parent' => $category->getId()],
                ]);
            }
        }
        return $menu;
    }

}

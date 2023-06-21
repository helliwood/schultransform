<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2022-07-29
 * Time: 9:30
 */

namespace Trollfjord\Controller;

use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Service\Dashboard\SchoolService;
use Knp\Menu\Provider\MenuProviderInterface;

/**
 * @IsGranted("ROLE_SCHOOL_LITE")
 * @Route("/Dashboard/School", name="dashboard_school_")
 */
class DashboardSchoolController extends AbstractController
{
    protected SchoolService $myService;

    public function __construct(MailerInterface $mailer, SchoolService $myService)
    {
        parent::__construct($mailer);
        $this->myService = $myService;

    }


    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function index(): Response
    {

        $user = $this->getUser();
        $data = $this->myService->getStarSiteData($user);
        return $this->render('frontend/dashboard-school/index.html.twig', array_merge($data, []));
    }

    /**
     * @Route("/potential/{category<\d+>?}", defaults={"category":null}, name="potential")
     * @param Category $category
     * @param MenuProviderInterface $menuProvider
     * @return Response
     * @throws Exception
     */
    public function potential(Category $category, MenuProviderInterface $menuProvider): Response
    {
        $menuProvider->get('frontend')['dashboard_school']->addChild("Bla", [
            'label' => $category->getName(),
            'route' => 'dashboard_school_potential',
            'routeParameters' => ['category' => $category->getId()],
            'display' => false
        ]);

        $user = $this->getUser();
        $data = $this->myService->potential($user, $category);
        //load the page only if data available
        if (!empty($data)) {
            return $this->render('frontend/dashboard-school/actions/potential.html.twig', array_merge($data, []));
        } else {
            return $this->redirectToRoute('dashboard_teacher_home');
        }
    }

    /**
     * @Route("/potential-questionnaire/{questionnaire<\d+>?}", defaults={"questionnaire":null}, name="potential_questionnaire")
     * @param Questionnaire $questionnaire
     * @param MenuProviderInterface $menuProvider
     * @return Response
     * @throws Exception
     */
    public function potentialQuestionnaire(Questionnaire $questionnaire, MenuProviderInterface $menuProvider): Response
    {
        $menuProvider->get('frontend')['dashboard_school']->addChild("Bla", [
            'label' => $questionnaire->getName(),
            'route' => 'dashboard_school_potential_questionnaire',
            'routeParameters' => ['questionnaire' => $questionnaire->getId()],
            'display' => false
        ]);
        $user = $this->getUser();
        $data = $this->myService->potentialQuestionnaire($user, $questionnaire);
        //load the page only if data available
        if (!empty($data)) {
            return $this->render('frontend/dashboard-school/actions/potential-questionnaire.html.twig', array_merge($data, []));
        } else {
            return $this->redirectToRoute('dashboard_school_home');
        }
    }

    /**
     * @Route("/get-partner-material/{material<\d+>?}", defaults={"material":null}, name="get_partner_material")
     * @param int $material
     * @return Response
     */
    public function getPartnerMaterial(int $material): Response
    {
        $response = $this->myService->getMaterial($material);

        if ($response) {
            return $response;
        }

        return $this->render('frontend/dashboard-school/actions/child-templates/material-not-found.html.twig', [
            'user', $this->getUser()->toArray(),
        ]);


    }

}

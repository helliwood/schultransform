<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2022-07-29
 * Time: 9:30
 */

namespace Trollfjord\Controller;

use Doctrine\DBAL\Exception;
use Knp\Menu\Provider\MenuProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Service\Dashboard\SchoolService;
use Trollfjord\Service\Dashboard\TeacherService;

/**
 * @IsGranted("ROLE_TEACHER")
 * @Route("/Dashboard/Teacher", name="dashboard_teacher_")
 */
class DashboardTeacherController extends AbstractController
{
    protected TeacherService $myService;

    public function __construct(MailerInterface $mailer, TeacherService $myService)
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
        return $this->render('frontend/dashboard-teacher/index.html.twig', array_merge($data, []));
    }

    /**
     * @Route("/category-overview/{category<\d+>?}", defaults={"category":null}, name="category_overview")
     * @param Category $category
     * @param MenuProviderInterface $menuProvider
     * @return Response
     */
    public function categoryOverview(Category $category, MenuProviderInterface $menuProvider): Response
    {
        $menuProvider->get('frontend')['dashboard']->addChild("dashboard_teacher_category_overview", [
            'label' => $category->getName(),
            'route' => 'dashboard_teacher_category_overview',
            'routeParameters' => ['category' => $category->getId()],
            'display' => false
        ]);
        $user = $this->getUser();
        $data = $this->myService->CategoryOverviewData($user, $category);
        //load the page only if data available
        if (!empty($data)) {
            return $this->render('frontend/dashboard-teacher/actions/category-overview.html.twig', array_merge($data, []));
        } else {
            return $this->redirectToRoute('dashboard_teacher_home');
        }
    }

    /**
     * @Route("/potential/{category<\d+>?}", defaults={"category":null}, name="potential")
     * @param Category $category
     * @param MenuProviderInterface $menuProvider
     * @param SchoolService $schoolService
     * @return Response
     * @throws Exception
     */
    public function potential(Category $category, MenuProviderInterface $menuProvider, SchoolService $schoolService): Response
    {
        $menuProvider->get('frontend')['dashboard']->addChild("Bla", [
            'label' => $category->getName(),
            'route' => 'dashboard_teacher_category_overview',
            'routeParameters' => ['category' => $category->getId()],
            'display' => false
        ])->addChild("potential", [
            'label' => "Potenzialanalyse",
            'route' => 'dashboard_teacher_potential',
            'routeParameters' => ['category' => $category->getId()]
        ]);
        $user = $this->getUser();
        $data = $this->myService->potential($user, $category);
        //load the page only if data available
        if (!empty($data)) {
            return $this->render('frontend/dashboard-teacher/actions/potential.html.twig', array_merge($data, []));
        } else {
            return $this->redirectToRoute('user_success');
        }
    }

    /**
     * @Route("/nextQuestionair/{category<\d+>?}", defaults={"category":null}, name="next_questionair")
     * @param Category $category
     * @return RedirectResponse
     */
    public function nextQuestionair(Category $category = null): Response
    {
        $user = $this->getUser();
        $data = $this->myService->getAllQuestionairsFormated($user->getId(), $category);
        $undoneQuestionairs = [];
        $prefered = null;
        //get prefered questionair:
        switch($user->getSchoolType()){

            //TODO: im Backend hinterlegen?
            case 'weiterführende Schule':
                $prefered = 3;
                break;
            case 'berufliche Schule':
                $prefered = 3;
                break;
            case 'Grundschule':
                $prefered = 12;
                break;

        }

        foreach ($data as $cat) {
            foreach ($cat as $questionaire) {
                if (!$questionaire['doneat'] || $questionaire['doneat'] == "") {
                    $undoneQuestionairs[] = $questionaire['slug'];
                }
                if($questionaire['id']===$prefered){
                    return $this->redirect($questionaire['slug']);
                }
            }
        }
        return $this->redirect($undoneQuestionairs[0]);
    }
}

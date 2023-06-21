<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2022-07-29
 * Time: 9:30
 */

namespace Trollfjord\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Menu\Provider\MenuProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Entity\School;
use Trollfjord\Service\Dashboard\SchoolAuthorityService;
use function array_merge;
use function date;


/**
 * @IsGranted("ROLE_SCHOOL_AUTHORITY_LITE")
 * @Route("/Dashboard/School-Authority", name="dashboard_school_authority_")
 */
class DashboardSchoolAuthorityController extends AbstractController
{

    protected SchoolAuthorityService $myService;

    public function __construct(MailerInterface $mailer, SchoolAuthorityService $myService)
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
        return $this->render('frontend/dashboard-school-authority/index.html.twig', array_merge($data, []));
    }

    /**
     * @Route("/category-overview/{category<\d+>?}", defaults={"category":null}, name="category_overview")
     * @param Category              $category
     * @param MenuProviderInterface $menuProvider
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    public function categoryOverview(Category $category, MenuProviderInterface $menuProvider): Response
    {
        $menuProvider->get('frontend')['dashboard_school_authority']->addChild("dashboard_teacher_category_overview", [
            'label' => $category->getName(),
            'route' => 'dashboard_school_authority_category_overview',
            'routeParameters' => ['category' => $category->getId()],
            'display' => false
        ]);
        $user = $this->getUser();

        $data = $this->myService->CategoryOverviewData($user, $category);
        //load the page only if data available
        if (! empty($data)) {
            return $this->render('frontend/dashboard-school-authority/actions/category-overview.html.twig', array_merge($data, []));
        } else {
            return $this->redirectToRoute('dashboard_teacher_home');
        }
    }


    /**
     * @Route("/potential/{school<\d+>?}", defaults={"school":null}, name="school_potential")
     * @param School                 $school
     * @param MenuProviderInterface  $menuProvider
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    public function potential(School $school, MenuProviderInterface $menuProvider, EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();
        $forceToStartSite = false;
        //check if the school belongs to the school authority if not go back to the start site
        if ($school->getSchoolAuthority()) {
            if ($user->getSchoolAuthority()->getId() !== $school->getSchoolAuthority()->getId()) {
                $forceToStartSite = true;
            }
        } else {
            $forceToStartSite = true;
        }
        if ($forceToStartSite) {
            //back to the start site
            return $this->redirectToRoute('dashboard_school_authority_home');
        }

        //check if the category and school exist
//        /** @var Category $category * */
//        $category = $entityManager->getRepository(Category::class)->find($categoryId);
//        $school = $entityManager->getRepository(School::class)->find($schoolId);

        $menuProvider->get('frontend')['dashboard_school_authority']->addChild("Blaa", [
            'label' => $school->getName(),
            //            'route' => 'dashboard_school_authority_home',
            //            'routeParameters' => ['school' => $school->getId()],
            'display' => false
        ])->addChild("potential", [
            'label' => "Potenzialanalyse",
            'route' => 'dashboard_school_authority_school_potential',
            'routeParameters' => ['school' => $school->getId()]
        ]);
//
//        $isUserAllow = false;
//        if ($category && $school) {
//            //check if the school has the current school authority assigned
//            /** @var User $user * */
//            $user = $this->getUser();
//            $schoolAuthority = $user->getSchoolAuthority();
//            if ($user->getSchoolAuthority()) {
//                if (count($user->getSchoolAuthority()->getSchools()) > 0) {
//                    //check the school
//                    if ($school->getSchoolAuthority()->getId() === $schoolAuthority->getId()) {
//                        $isUserAllow = true;
//                    }
//                }
//            }
//        }
//
//        if (!$isUserAllow) {
//            return $this->redirectToRoute('dashboard_school_authority_home');
//        }

        $data = $this->myService->potential($school);
        $questionnaires = $this->myService->getAllQuestionnairesFromSchool($school);

        //load the page only if data available
        if (! empty($data)) {
            return $this->render('frontend/dashboard-school-authority/actions/potential.html.twig', array_merge($data, ['questionnaires' => $questionnaires]));
        } else {
            return $this->redirectToRoute('dashboard_school_authority_home');
        }
    }

    /**
     * @Route("/nextQuestionair/{category<\d+>?}", defaults={"category":null}, name="next_questionnaire")
     * @param Category|null $category
     * @return RedirectResponse
     */
    public function nextQuestionnaire(Category $category = null): Response
    {
        $user = $this->getUser();
        $data = $this->myService->getAllQuestionairsFormated($user->getId(), $category);
        $undoneQuestionairs = [];
        $prefered = null;
        //get prefered questionair:
        switch ($user->getSchoolType()) {

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
                if (! $questionaire['doneat'] || $questionaire['doneat'] == "") {
                    $undoneQuestionairs[] = $questionaire['slug'];
                }
                if ($questionaire['id'] === $prefered) {
                    return $this->redirect($questionaire['slug']);
                }
            }
        }
        return $this->redirect($undoneQuestionairs[0]);
    }

    /**
     * @Route("/request-school-more-info", name="request_school_more_info")
     * @param Request                $request
     * @param EntityManagerInterface $entityManager
     * @param MailerInterface        $mailer
     * @return JsonResponse
     * @throws TransportExceptionInterface
     */
    public function requestMoreSchoolInfo(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {

        $schoolId = $request->request->get('schoolId', null);
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();
        $success = false;
        $message = '';
        $error = 'Es gab ein Problem beim Versenden der E-Mail. Bitte versuchen Sie es noch einmal oder wenden Sie sich an Ihren technischen Support.';
        if ($schoolId) {
            if ($school = $entityManager->getRepository(School::class)->find($schoolId)) {
                $emailTemplate = 'mail/request_more_info_to_school.html.twig';
                try {
                    $email = (new TemplatedEmail())
                        ->subject('Anfrage nach weiteren Informationen über Ihre Schule')
                        ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                        ->to('mayoral@helliwood.com')
//                    ->to($school->getMainUser()->getEmail())
                        ->htmlTemplate($emailTemplate)
                        ->context(
                            ['school' => $school, 'schoolAuthority' => $schoolAuthority, 'userReceiver' => $school->getMainUser()]
                        );

                    $mailer->send($email);
                    $message = 'eine E-Mail wurde erfolgreich an den Leiter der Schule geschickt';
                    $success = true;
                    $error = '';
                } catch (Exception $e) {

                }

            }
        }
        $toReturn = [
            'error' => $error,
            'success' => $success,
            'message' => $message,
        ];

        return new JsonResponse($toReturn, 200);


    }



//    /**
//     * @Route("/dashboard", name="dashboard")
//     * @param Request $request
//     * @param SessionInterface $session
//     * @param QuestionnaireService $questionnaireService
//     * @return Response
//     */
//    public function dashboard(Request $request, SessionInterface $session, QuestionnaireService $questionnaireService): Response
//    {
//        $user = $this->getUser();
//        $unfinished = []; // array that includes all Categories with no finished Questionaires
//        $finished = []; // array that includes all Categories with  finished Questionaires
//        $downloads = []; //Collects all avaiable downloads per Category
//        $dates = []; // includes all the dates + amount of actions per category that where done
//        $userDates = []; // same as $dates but only for the questionaires made by the user
//        $questionnaireResults = []; //includes the (average) results of each questionaire
//        $personalChartTitle = 'Meine ID: ' . $user->getCode();
//        $chart2 = [];
//        $doneQuestionaires = [];
//        $schools = [];
//        $allUndoneQuestionnaires = [];
//
//
//        // MAURICE: Das Form muss ganz nach oben weil es durch
//        // die ganze Entitätenmanipulation einen Fehler beim flushen gibt.
//        //Create form for school connection
//        $linkSchoolForm = $this->createForm(FormType::class);
//        $linkSchoolForm->add('schoolCode', TextType::class, ['constraints' => [new NotBlank()]]);
//        $linkSchoolForm->handleRequest($request);
//        if ($linkSchoolForm->isSubmitted() && $linkSchoolForm->isValid()) {
//            $school = $this->getDoctrine()->getRepository(School::class)->findOneBy(['code' => $linkSchoolForm->getData()['schoolCode']]);
//            if (is_null($school)) {
//                $linkSchoolForm->get('schoolCode')->addError(new FormError('Schulcode nicht gefunden!'));
//            } elseif ($school->getSchoolType() !== $this->getUser()->getSchoolType()) {
//                $linkSchoolForm->get('schoolCode')->addError(new FormError('Der Schultyp (' . $school->getSchoolType() . ') ist nicht mit Ihrem Code (' . $this->getUser()->getSchoolType() . ') kompatibel!'));
//            } else {
//                $this->getUser()->setSchool($school);
//                $this->getDoctrine()->getManager()->flush();
//                return $this->redirectToRoute('user_success');
//            }
//        }
//
//
//        //fetch all made results for activityindex
//        if ($this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE")) {
//            $users = $user->getSchoolAuthority()->getUsers();
//            foreach ($user->getSchoolAuthority()->getSchools() as $school) {
//                $schools[] = $school;
//            }
//
//            $utemp = [];
//            foreach ($users as $val) {
//                $utemp[] = $val->getId();
//            }
//            $chartTitle = "Meine Schulen: ";
//        } elseif ($user->getSchool()) {
//            //if the user is associated with a school, we need the results from every user whos assigned to this school
//            $users = $user->getSchool()->getUsers();
//            $utemp = [];
//            foreach ($users as $val) {
//                $utemp[] = $val->getId();
//            }
//            $chartTitle = "Meine Schule: " . $user->getSchool()->getName();
//            $secondChartTitle = $personalChartTitle;
//
//        } else {
//            $utemp[] = $user->getId();
//            $chartTitle = $personalChartTitle;
//        }
//
//        //GET all existing Results to show activity chart
//        list($earliestDate, $catId, $formatedDate, $dates, $userDates) = $this->activityresults($utemp, $dates, $userDates, $user);
//
//        if (($this->isGranted("ROLE_SCHOOL_LITE") && $user->getSchool()) || $this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE")) {
//            $resultsUser = $utemp;
//        } else {
//            $resultsUser = $user->getId();
//        }
//        $results = $questionnaireService->getQuestionaireResultsByUser($resultsUser);
//
//
//        //Go trough results and fill up with downloads
//        foreach ($results as $res) {
//            $catId = $res['categoryId'];
//            $questName = $res['questionnaireName'];
//            $questId = $res['questionnaireId'];
//            $resUserId = $res['userId'];
//            $resultId = $res['resultId'];
//            $resultCount = $res['results'];
//            $resultsIn[$catId][] = true;
//            $downloadUrl = '';
//            $resultUrl = '';
//
//            //check if theres a page accociated with the current questionaire and put it to the dowwnloads
//
//            if ($res['Site'] != '') {
//                $site = $this->getDoctrine()->getRepository(Site::class)->find($res['Site']);
//                $resultUrl = $site->getUrl();
//                //$download = new DownloadContainer('Materialien zum Thema:  ' . $questName . '', '', $site->getUrl(), 'Link aufrufen', DownloadContainer::TYPE_LINK);
//                //$downloads[$catId][md5($site->getSlug())] = $download->getArray();
//            }
//
//
//            //if it's a result from the current user, put result pdf into downloads
//            if ($resUserId == $user->getId()) {
//                if (!isset($downloads[$catId][$questName])) {
//
//
//                    $downloadUrl = '/print-result/' . $resultId;
//                    $download = new DownloadContainer('Ihre Momentaufnahme zum Thema:  ' . $questName . '', 'ausgefüllt am ' . $formatedDate, $downloadUrl, 'Herunterladen (PDF)', DownloadContainer::TYPE_DOWNLOAD);
//                    $downloads[$catId][$questName] = $download->getArray();
//                    $chartDetail = 'Stand: ' . date('d.m.Y H:i', strtotime($res['creationDate']));
//                    if ($resultUrl != '') {
//                        $chartDetail .= ' | <a href="' . $resultUrl . '">jetzt wiederholen</a>';
//                    }
//                }
//            }
//
//            //If User is schoolmanager, we also need the summary in the downloadssection
//            if ($this->isGranted("ROLE_SCHOOL_LITE") || $this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE")) {
//                if (!isset($downloads[$catId][$questName])) {
//                    $downloadUrl = '/Questionnaire/school-questionnaire-result/' . $questId;
//                    $mehrz = "Schule";
//                    if ($this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE")) {
//                        $mehrz = "Schulen";
//                        $downloadUrl = '/Questionnaire/schoolauthority-questionnaire-result/' . $questId;
//
//                    }
//                    $download = new DownloadContainer('Momentaufnahme Ihrer ' . $mehrz . ' zum Thema:  ' . $questName . '', '', $downloadUrl, 'Link aufrufen', DownloadContainer::TYPE_LINK);
//                    $downloads[$catId][$questName] = $download->getArray();
//
//                    // PDF ergänzen für Schule
//                    if ($this->isGranted("ROLE_SCHOOL_LITE")) {
//                        $downloadUrl = '/Questionnaire/print-school-questionnaire-result/' . $questId;
//                        $download = new DownloadContainer('Momentaufnahme Ihrer ' . $mehrz . ' zum Thema:  ' . $questName . '', '', $downloadUrl, 'Herunterladen (PDF)', DownloadContainer::TYPE_DOWNLOAD);
//                        $downloads[$catId][$questName . '2'] = $download->getArray();
//
//                    }
//
//                    $chartDetail = 'basierend auf ' . $resultCount;
//                    if ($resultCount > 1) {
//                        $chartDetail .= ' Fragebögen';
//                    } else {
//                        $chartDetail .= ' Fragebogen';
//                    }
//                }
//            }
//
//
//            if (!isset($questionnaireResults[$catId][$questName])) {
//                $doneQuestionaires[$questId] = $questId;
//                $questionnaireResults[$catId][$questName] = ['val' => $res['value'], 'questId' => $questId, 'url' => $downloadUrl, 'detail' => $chartDetail];
//            }
//
//
//        }
//
//
//        //get cats with recomendations
//        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();
//        $recommandations = $this->fetchRecommandations();
//        $catDetails = [];
//
//
//        //go trough categories and prepare final array
//        foreach ($categories as $cat) {
//            $recommandation = [];
//            $downloadList = [];
//            $chartData = [];
//            $url = "";
//            $questionaires = [];
//            $undoneQuestionnaires = [];
//
//            if (isset($recommandations[$cat->getId()])) {
//                $recommandation = $recommandations[$cat->getId()];
//            }
//            if (isset($questionnaireResults[$cat->getId()])) {
//                $chartData = $questionnaireResults[$cat->getId()];
//            }
//            if (isset($downloads[$cat->getId()])) {
//                $downloadList = $downloads[$cat->getId()];
//            }
//
//
//            $catSite = $cat->getSite();
//            if ($catSite) {
//                $url = $cat->getSite()->getUrl();
//            }
//
//
//            //get all Questionaires that aren't done yet
//            $questionaires = $cat->getQuestionnaires();
//            foreach ($questionaires as $questionaire) {
//                if (
//                    ($questionaire->getType() == Questionnaire::TYPE_SCHOOL && $this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE"))
//                    ||
//                    ($questionaire->getType() == Questionnaire::TYPE_SCHOOL_BOARD && !$this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE"))
//                ) {
//                    continue;
//                }
//
//                $questSlug = $url;
//                if (!isset($doneQuestionaires[$questionaire->getId()])) {
//                    if ($questionaire->getSite()) {
//                        $questSlug = $questionaire->getSite()->getUrl();
//                    }
//                    $undoneQuestionnaires[$questionaire->getId()] = array('name' => $questionaire->getName(), 'icon' => $questionaire->getIcon(), 'site' => $questSlug);
//                }
//
//
//                if (($questionaire->getType() == Questionnaire::TYPE_SCHOOL_BOARD && $this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE"))
//                ) {
//
//
//                    $url = $questSlug;
//                }
//
//            }
//
//
//            // get the Data for the Gauge charts
//            foreach ($chartData as $chartTempId => $chartTempVal) {
//                $questionaires[] = ['name' => $chartTempId, 'value' => $chartTempVal];
//            }
//
//            $categorieTempray = [
//                'title' => $cat->getName(),
//                'icon' => $cat->getIcon(),
//                'color' => $cat->getColor(),
//                'handlungsempf' => $recommandation,
//                'downloads' => $downloadList,
//                'url' => $url,
//                'charts' => $chartData,
//                'undone' => $undoneQuestionnaires
//            ];
//
//
//            $catDetails[$cat->getId()] = ['color' => $cat->getColor(), 'title' => $cat->getName()];
//
//
//            //if we have recommendations or results, we put it to the finished
//            if (count($recommandation) >= 1 || isset($resultsIn[$cat->getId()])) {
//                $finished[$cat->getId()] = $categorieTempray;
//            } else { //otherwise to unfinished
//                $unfinished[] = $categorieTempray;
//            }
//        }
//
//
//        //if the user has code + school we need to create two activity charts
//        $chart1 = $this->createActivityArray($chartTitle, $dates, $earliestDate->format('d.m.Y'), $catDetails);
//        if ($user->getSchool() && $user->getCode()) {
//            $chart2 = $chart1;
//            $chart1 = $this->createActivityArray($secondChartTitle, $userDates, $earliestDate->format('d.m.Y'), $catDetails);
//        }
//
//        //get the twig and render everything
//        return $this->render('frontend/old_dashboard_files/success.html.twig',
//            [
//                "user" => $user,
//                "results" => $finished,
//                "unfinished" => $unfinished,
//                "chart1" => json_encode($chart1),
//                "chart2" => json_encode($chart2),
//                "linkSchoolForm" => $linkSchoolForm->createView(),
//                "schools" => $schools
//            ]
//        );
//    }

    /**
     * @param array $utemp
     * @param array $dates
     * @param array $userDates
     * @param User  $user
     * @return array
     */
    private function activityresults(array $utemp, array $dates, array $userDates, User $user): array
    {
        $ActivityResults = $this->getDoctrine()->getRepository(Result::class)->findBy(['user' => $utemp], ['id' => 'DESC']);
        $catId = $formatedDate = null;
        //get day one month before
        $earliestDate = new \DateTime();
        $earliestDate->modify('-1 month');

        //Go trough all result and create Date-Array for activities
        foreach ($ActivityResults as $activity) {
            $catId = $activity->getQuestionnaire()->getCategory()->getId();

            //Take Care that we start with the earliest Date with an interaction
            $formatedDate = $activity->getCreationDate();
            if ($formatedDate < $earliestDate) {
                $earliestDate = $formatedDate;
            }
            $formatedDate = $formatedDate->format('d.m.Y');


            //Create arrays when not existing
            if (! isset($dates[$catId][$formatedDate])) {
                $dates[$catId][$formatedDate] = 0;
                $userDates[$catId][$formatedDate] = 0;
            }

            //Count up
            $dates[$catId][$formatedDate] = $dates[$catId][$formatedDate] + 1;
            if ($activity->getUser()->getId() == $user->getId()) {
                $userDates[$catId][$formatedDate] = $userDates[$catId][$formatedDate] + 1;
            }
        }
        return [$earliestDate, $catId, $formatedDate, $dates, $userDates];
    }

    private function fetchRecommandations()
    {
        $recommandations = [];
        $catsarray = [];
        /** @var User $user */
        $user = $this->getUser();

        $conn = $this->getDoctrine()->getConnection();
        if ($this->isGranted("ROLE_SCHOOL_LITE")) {
            // Wir sind eine Schule
            $where = ' and school_id =' . $user->getSchool()->getId();
        } else {
            $userId = $user->getId();
            $where = ' and user_id= ' . $userId . '';
        }

        //$this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE") // Der Schulträger


        $sql = 'Select questionnairebundle_result.id as resuId,recommendation_id,category_id,questionnairebundle_questionnaire.name as questName from questionnairebundle_result_answer
                    inner join questionnairebundle_result on questionnairebundle_result.id = questionnairebundle_result_answer.result_id
                    inner join publicuserbundle_user on publicuserbundle_user.id = user_id  
                    inner join  (select max(id) as foo from questionnairebundle_result group by user_id,questionnaire_id)  latest on latest.foo = questionnairebundle_result.id
                    inner join questionnairebundle_question on questionnairebundle_question.id = questionnairebundle_result_answer.question_id
                    inner join questionnairebundle_questionnaire  on questionnairebundle_result.questionnaire_id = questionnairebundle_questionnaire.id
                where questionnairebundle_result_answer.value <= 3
                    and questionnairebundle_question.recommendation_id >0
                   ' . $where . '
                group by  category_id,recommendation_id
                order by questionnairebundle_questionnaire.position asc,questionnairebundle_question.position asc               
                ';

        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();
        foreach ($ret as $retrow) {
            if (! isset($recommandations[$retrow['category_id']])) {
                $recommandations[$retrow['category_id']] = [];
            }
            $catsarray[$retrow['category_id']][$retrow['questName']][] = $retrow['recommendation_id'];
            $recommandations[] = $retrow['recommendation_id'];
        }


        $foo = $this->getDoctrine()->getRepository(Recommendation::class)->findBy(['id' => $recommandations]);
        $recommandations = [];
        foreach ($foo as $rec) {
            $recommandations[$rec->getId()] = [
                'title' => $rec->getTitle(),
                'description' => $rec->getDescription(),
                'implementation' => $rec->getImplementation(),
                'tips' => $rec->getTips(),
                'examples' => $rec->getExamples(),
                'additionalInformation' => $rec->getAdditionalInformation(),
                'tipsSchoolManagement' => $rec->getTipsSchoolManagement()
            ];
        }


        $retu = [];
        foreach ($catsarray as $cat => $catVal) {
            foreach ($catVal as $catrec => $va) {
                foreach ($va as $foi) {
                    $retu[$cat][$catrec][] = $recommandations[$foi];
                }
            }
        }
        return $retu;
    }

    private function createActivityArray($title = "", $categories, $StartDay, $catDetails, $format = 'd.m.Y')
    {
        $catVals = $this->CreateDatesArray($categories, $StartDay, $catDetails, $format);
        $activities = [
            'grid' => ['top' => '25px', 'left' => '25px'],
            'textStyle' => ['fontFamily' => "regular", 'fontWeight' => "normal", 'fontSize' => "12"],
            'title' => ['text' => $title, 'left' => '0', 'top' => -5, 'textStyle' => ['fontFamily' => "regular", 'fontWeight' => "normal", 'fontSize' => "14"]],
            'tooltip' => ['trigger' => 'axis'],
            'xAxis' =>
                [
                    'type' => 'category',
                    'splitLine' => ['show' => true],
                    'axisLabel' => ['show' => true, 'fontFamily' => "regular", 'fontWeight' => "normal", 'fontSize' => "12"],
                    'data' =>
                        $catVals[1],
                ],
            'yAxis' =>
                [
                    'type' => 'value',

                    'min' => 0,
                    'max' => 6,
                    'splitLine' => ['show' => true],
                    'splitNumber' => 3,
                    'axisTick' => [
                        'show' => false,
                        'stepSize' => 2
                    ]
                ],
            'series' =>
                $catVals[0]
        ];
        return $activities;

    }

    private function CreateDatesArray($categories, $StartDay, $catDetails, $format = 'd.m.Y')
    {
        $dates = [];
        $ret = [];
        $current = strtotime($StartDay);
        $date2 = strtotime("now");
        $stepVal = '+1 day';

        while ($current <= $date2) {
            $dates[date($format, $current)] = 0;
            $current = strtotime($stepVal, $current);
        }

        foreach ($categories as $cat => $CatDates) {
            $resList = array_merge($dates, $categories[$cat]);
            $ret[] =
                [
                    'data' =>
                        array_values($resList),
                    'type' => 'line',
                    'name' => $catDetails[$cat]['title'],
                    'lineStyle' =>
                        [
                            'color' => $catDetails[$cat]['color'],
                        ],
                    'areaStyle' =>
                        [
                            'color' => $catDetails[$cat]['color'],
                            'opacity' => 0.8,
                        ],
                    'color' => $catDetails[$cat]['color']
                ];
        }

        return [$ret, array_keys($dates)];
    }

}

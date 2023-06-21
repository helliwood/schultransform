<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\ControllerPublic;

use Dompdf\Dompdf;
use Exception;
use ReflectionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Service\QuestionnaireExcelService;
use Trollfjord\Bundle\QuestionnaireBundle\Service\QuestionnaireService;
use Trollfjord\Bundle\QuestionnaireBundle\Service\ResultService;
use Trollfjord\Core\Controller\AbstractPublicController;
use Trollfjord\Entity\School;
use function date;
use function json_decode;


/**
 * Class QuestionnaireController
 *
 * @author Juan Mayoral <mayoral@helliwood.com>
 *
 * @method User getUser
 * @Route("/Questionnaire", name="questionnaire_")
 */
class QuestionnaireController extends AbstractPublicController
{

    /**
     * @Route("/test/{slug}", name="test")
     * @param int                  $slug
     * @param QuestionnaireService $questionnaireService
     * @return Response
     */
    public function getQuestionsTest(int $slug, QuestionnaireService $questionnaireService): Response
    {

        $questions = $questionnaireService->getQuestionnaireData($slug);
        return $this->render('@Questionnaire/questionnaire/index.html.twig', [
            'questionnaireId' => $questions[0]['id'],
        ]);

    }

    /**
     * @Route("/get/{id}", name="get_questionnaire_ajax")
     * @param int                  $id
     * @param QuestionnaireService $questionnaireService
     * @param SessionInterface     $session
     * @param Request              $request
     * @return JsonResponse
     * @throws ReflectionException
     */
    public function getQuestionnaire(int $id, QuestionnaireService $questionnaireService, SessionInterface $session, Request $request): JsonResponse
    {
        $questionnaire = $questionnaireService->getQuestionnaire($id);
        if ($questionnaire) {
            $questionnaire->setCurrentSchoolType($this->getUser()->getSchoolType());
            $questionnaireAsArray = $questionnaire->toArray4Form();
            if ($this->getUser()) {
                $questionnaireAsArray['user'] = $questionnaireService->userFilledOutQuestionnaire($this->getUser()->getId(), $id);
            }
            return new JsonResponse([$questionnaireAsArray]);
        }
        throw $this->createNotFoundException('Questionniare nicht gefunden!');
    }

    /**
     * @Route("/save", name="save_questionnaire_ajax")
     * @param Request       $request
     * @param ResultService $resultService
     * @return JsonResponse
     * @throws Exception
     */
    public function saveQuestionnaire(Request $request, ResultService $resultService): JsonResponse
    {

        if (! $this->getUser() || (! $this->getUser()->hasRole("ROLE_TEACHER") && ! $this->getUser()->hasRole("ROLE_SCHOOL_AUTHORITY") && ! $this->getUser()->hasRole("ROLE_SCHOOL_AUTHORITY_LITE"))) {
            throw $this->createAccessDeniedException('Keine Berechtigung für diesen Fragebogen!');
        }
        $resultService->saveResult($this->getUser(), json_decode($request->getContent(), true)['questionnaireData']);

        return new JsonResponse('SAVED');
    }

    /**
     * @Route("/chart-data", name="chart_data")
     * @param QuestionnaireService $questionnaireService
     * @return JsonResponse
     * @throws \Doctrine\DBAL\Exception
     */
    public function chartData(QuestionnaireService $questionnaireService): JsonResponse
    {
        return new JsonResponse($questionnaireService->getChartData());
    }


    /**
     * @Route("/school-questionnaire-result/{id}", name="school_questionnaire_result")
     * @IsGranted("ROLE_SCHOOL_LITE")
     * @param Questionnaire        $questionnaire
     * @param QuestionnaireService $questionnaireService
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    public function schoolQuestionnaireResult(Questionnaire $questionnaire, QuestionnaireService $questionnaireService): Response
    {
        $questionnaire->setCurrentSchoolType($this->getUser()->getSchool()->getSchoolType());
        $result = $questionnaireService->getQuestionnaireResultBySchool($questionnaire, $this->getUser()->getSchool());
        return $this->render('@Questionnaire/questionnaire/school_questionnaire_result.html.twig', [
            'questionnaire' => $questionnaire,
            'result' => $result
        ]);
    }

    /**
     * @Route("/print-school-questionnaire-result/{id}", name="school_questionnaire_result_print")
     * @param Questionnaire        $questionnaire
     * @param QuestionnaireService $questionnaireService
     * @throws Exception
     */
    public function print(Questionnaire $questionnaire, QuestionnaireService $questionnaireService)
    {
        $questionnaire->setCurrentSchoolType($this->getUser()->getSchool()->getSchoolType());
        $result = $questionnaireService->getQuestionnaireResultBySchool($questionnaire, $this->getUser()->getSchool());
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $content = $this->render('@Questionnaire/questionnaire/print_school_questionnaire_result.html.twig', [
            'questionnaire' => $questionnaire,
            'result' => $result,
            'school' => $this->getUser()->getSchool(),
            'index' => $questionnaireService->getSchoolIndexForQuestionnaire($this->getUser()->getSchool(), $questionnaire)
        ])->getContent();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($questionnaire->getCategory()->getName() . "-" . date("d.m.Y") . ".pdf");
    }

    /**
     * @Route("/print-school-questionnaire-result-by-school/{school}/{questionnaire}", name="school_questionnaire_result_print_by_school")
     * @param School               $school
     * @param Questionnaire        $questionnaire
     * @param QuestionnaireService $questionnaireService
     * @throws \Doctrine\DBAL\Exception
     */
    public function printBySchool(School $school, Questionnaire $questionnaire, QuestionnaireService $questionnaireService, Request $request)
    {
        if ($this->getUser() && $this->getUser()->getSchoolAuthority()) {
            if ($this->getUser()->getSchoolAuthority() !== $school->getSchoolAuthority()) {
                throw $this->createAccessDeniedException('Zutritt verweigert');
            }
        } else {
            if (! $request->query->has('pass') || $request->query->get('pass') != 'Helliwood!2020') {
                throw $this->createAccessDeniedException('Bad Password');
            }
        }
        $questionnaire->setCurrentSchoolType($school->getSchoolType());
        $result = $questionnaireService->getQuestionnaireResultBySchool($questionnaire, $school);
        if (count($result['results']) <= 0) {
            throw $this->createNotFoundException('Kein Ergebnis gefunden!');
        }
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $content = $this->render('@Questionnaire/questionnaire/print_school_questionnaire_result.html.twig', [
            'questionnaire' => $questionnaire,
            'result' => $result,
            'school' => $school,
            'index' => $questionnaireService->getSchoolIndexForQuestionnaire($school, $questionnaire)
        ])->getContent();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($questionnaire->getCategory()->getName() . "-" . date("d.m.Y") . ".pdf");
    }

    /**
     * @Route("/print-school-questionnaire-result-school-authority/{school<\d+>?}/{questionnaire<\d+>?}", defaults={"school":null,"questionnaire":null}, name="school_questionnaire_result_print_school_authority")
     * @param School               $school
     * @param Questionnaire        $questionnaire
     * @param QuestionnaireService $questionnaireService
     * @throws \Doctrine\DBAL\Exception
     */
    public function printSchoolAuthority(School $school, Questionnaire $questionnaire, QuestionnaireService $questionnaireService)
    {
        $questionnaire->setCurrentSchoolType($school->getSchoolType());
        $result = $questionnaireService->getQuestionnaireResultBySchool($questionnaire, $school);
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $content = $this->render('@Questionnaire/questionnaire/print_school_questionnaire_result.html.twig', [
            'questionnaire' => $questionnaire,
            'result' => $result
        ])->getContent();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($questionnaire->getCategory()->getName() . "-" . date("d.m.Y") . ".pdf");
    }

    /**
     * @Route("/school-questionnaire-result-excel/{id}", name="school_questionnaire_result_excel")
     * @IsGranted("ROLE_SCHOOL_LITE")
     * @param Questionnaire             $questionnaire
     * @param QuestionnaireExcelService $questionnaireExcelService
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function schoolQuestionnaireResultExcel(Questionnaire $questionnaire, QuestionnaireExcelService $questionnaireExcelService): Response
    {
        $xlsx = $questionnaireExcelService->getSchoolExport4Questionnaire($this->getUser()->getSchool(), $questionnaire);

        $response = new StreamedResponse(
            static function () use ($xlsx): void {
                $xlsx->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Fragebogen_' . $questionnaire->getName() . '_' . \date('Y-m-d-H-i') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @Route("/schoolauthority-questionnaire-result/{id}", name="schoolauthority_questionnaire_result")
     * @IsGranted("ROLE_SCHOOL_AUTHORITY_LITE")
     * @param Questionnaire        $questionnaire
     * @param QuestionnaireService $questionnaireService
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    public function schoolauthorityQuestionnaireResult(Questionnaire $questionnaire, QuestionnaireService $questionnaireService): Response
    {
        $result = $questionnaireService->getQuestionnaireResultBySchoolAuthority($questionnaire, $this->getUser()->getSchoolAuthority());
        return $this->render('@Questionnaire/questionnaire/school_questionnaire_result.html.twig', [
            'questionnaire' => $questionnaire,
            'result' => $result
        ]);
    }

    /**
     * @Route("/schoolauthority-schools-questionnaire-result-excel/{id}", name="schoolauthority_schools_questionnaire_result_excel")
     * @IsGranted("ROLE_SCHOOL_AUTHORITY_LITE")
     * @param Category                  $category
     * @param QuestionnaireExcelService $questionnaireExcelService
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function schoolauthoritySchoolsQuestionnaireResultExcel(Category $category, QuestionnaireExcelService $questionnaireExcelService): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        $xlsx = $questionnaireExcelService->getSchoolAuthoritySchoolsExportByCategory($schoolAuthority, $category);

        $response = new StreamedResponse(
            static function () use ($xlsx): void {
                $xlsx->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Fragebogen_' . $category->getName() . '_' . \date('Y-m-d-H-i') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @Route("/all-schools-questionnaire-result-excel/{id}/{schoolType}", defaults={"schoolType":"all"}, requirements={"schoolType":"all|mint|not_mint"}, name="all_schools_questionnaire_result_excel")
     * @param Category                  $category
     * @param string                    $schoolType
     * @param QuestionnaireExcelService $questionnaireExcelService
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function allSchoolsQuestionnaireResultExcel(Category $category, string $schoolType, QuestionnaireExcelService $questionnaireExcelService): Response
    {
        $xlsx = $questionnaireExcelService->getAllSchoolsExportByCategory($category, $schoolType);

        $response = new StreamedResponse(
            static function () use ($xlsx): void {
                $xlsx->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Fragebogen_' . $category->getName() . '_' . $schoolType . '_' . \date('Y-m-d-H-i') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @Route("/school-excel-export", name="school_excel_export")
     * @param QuestionnaireExcelService $questionnaireExcelService
     * @param bool                      $onlyGermanSchools
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function SchoolExcelExport(QuestionnaireExcelService $questionnaireExcelService, bool $onlyGermanSchools = false): Response
    {
        $xlsx = $questionnaireExcelService->getSchoolExcelExport();

        $response = new StreamedResponse(
            static function () use ($xlsx): void {
                $xlsx->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Schulen_' . \date('Y-m-d-H-i') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @Route("/questionnaire-result-excel/{id}/{onlyGermanSchools}", name="questionnaire_result_excel")
     * @param Category                  $category
     * @param QuestionnaireExcelService $questionnaireExcelService
     * @param bool                      $onlyGermanSchools
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function questionnaireResultExcel(Category $category, QuestionnaireExcelService $questionnaireExcelService, bool $onlyGermanSchools = false): Response
    {
        $xlsx = $questionnaireExcelService->getExportByCategory($category, $onlyGermanSchools);

        $response = new StreamedResponse(
            static function () use ($xlsx): void {
                $xlsx->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Fragebogen_' . $category->getName() . '_' . \date('Y-m-d-H-i') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @Route("/result-excel", name="result_excel")
     * @param QuestionnaireExcelService $questionnaireExcelService
     * @return Response
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function resultExcel(QuestionnaireExcelService $questionnaireExcelService): Response
    {
        $xlsx = $questionnaireExcelService->getResultExport();

        $response = new StreamedResponse(
            static function () use ($xlsx): void {
                $xlsx->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Ergebnisse_' . \date('Y-m-d-H-i') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @Route("/result-boxplot/{all}", name="result_boxplot")
     * @param QuestionnaireExcelService $questionnaireExcelService
     * @param bool                      $all
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    public function resultBoxplot(QuestionnaireExcelService $questionnaireExcelService, bool $all = false): Response
    {
        $values = $questionnaireExcelService->getResult4Bloxplot($all);

        return $this->render('@Questionnaire/questionnaire/result_boxplot.html.twig', [
            "values" => $values,
            "all" => $all
        ]);
    }
}

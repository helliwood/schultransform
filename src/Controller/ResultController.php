<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-09-04
 * Time: 6:30
 */

namespace Trollfjord\Controller;

use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Bundle\QuestionnaireBundle\Service\ResultService;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Service\ChartService;
use Trollfjord\Service\QuestionaireDataService;


class ResultController extends AbstractController
{


    private $cat_quest = array();

    /**
     * @Route("/results/{cat}", name="resultGenerator")
     * @return Response
     */
    public function index(int $cat, QuestionaireDataService $questionaireDataService): Response
    {

        return new Response($questionaireDataService->getFullResults());
    }


    private function makeTree()
    {

    }

    /**
     * @Route("/chart-results", name="chart_ajax")
     *
     * @param Request      $request
     * @param ChartService $chartService
     * @return false|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function ajax(Request $request, ChartService $chartService)
    {
        if (false !== ($res = $chartService->execute($request))) {
            return $res;
        }

        throw new \Exception('no chart result!');
    }


    /**
     * @Route("/results-list", name="results_list")
     *
     * @param ResultService $resultService
     * @return Response
     */
    public function resultsList(ResultService $resultService): Response
    {
        $results = $resultService->getResultsByUser($this->getUser());

        return $this->render('/frontend/result/results_list.html.twig', [
            'results' => $results
        ]);
    }

    /**
     * @Route("/print-result/{resultId}", name="print-result")
     * @param $resultId
     * @throws Exception
     */
    public function print($resultId = null)
    {
        if (! is_null($resultId)) {
            $result = $this->getDoctrine()->getRepository(Result::class)->findOneBy(['id' => $resultId]);

            if ($result instanceof Result) {

                /** @var User $user */
                $user = $this->getUser();
                if ($result->getUser()->getId() == $user->getId()) {
                    $dompdf = new Dompdf(array('isPhpEnabled' => true));
                    $content = $this->render('pdf/result.html.twig', ['result' => $result])->getContent();
                    // replace relative Links to absolute in pdf
                    $content = preg_replace("/(href=[\"'])(\/[A-Za-z0-9_-][^\"']*)([\"'])/Uim", '$1' . 'https://www.schultransform.org' . '$2' . '$3', $content);
                    $dompdf->loadHtml($content);
                    $dompdf->setPaper('A4', 'portrait');
                    $dompdf->render();
                    $dompdf->stream($result->getQuestionnaire()->getCategory()->getName() . "-" . date("d.m.Y") . ".pdf");
                } else {
                    throw new Exception('Das Ergebnis ist nicht diesem Nutzer zugeordnet!');
                }

                /**
                 *                  For Developing only!
                 *
                 */
//                dump($result->getAnswers()->first());die;
//                return $this->render('/pdf/result.html.twig', [
//                    'result' => $result
//                ]);
            }
        } else {
            throw new Exception('Das Ergebnis konnte nicht gefunden werden!');
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-09-04
 * Time: 6:30
 */

namespace Trollfjord\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\QuestionnaireBundle\Service\TypeFormService;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Entity\QuestionaireQuestion;


class TypeController extends AbstractController
{
    /**
     * @Route("/typeform", name="Typeform generator")
     * @return Response
     */
    public function index(Request $request, TypeFormService $typeFormService): Response
    {

        $entityManager = $this->getDoctrine()->getManager();
        $qr = $entityManager->getRepository(QuestionaireQuestion::class);

        $res = json_decode($request->getContent(), true);
        // Maurice start
        $typeFormService->saveResult($res);
        // Maurice end :D
        $res = $res['form_response'];

        $definitions = $res['definition']['fields'];
        $answers = $res['answers'];
        $nrOfDefs = count($definitions);

        if (isset($res['hidden']['usertoken'])) {
            $usertoken = $res['hidden']['usertoken'];
        }
        $formId = $res['definition']['id'];

        for ($i = 0; $i < $nrOfDefs; $i++) {
            $reference = $definitions[$i]['ref'];
            $type = $answers[$i]['type'];
            $answertext = "-";

            $question = $qr->findby(['reference' => $reference, 'usertoken' => $usertoken, 'description' => $formId]);

            if (isset($question[0])) {
                $question = $question[0];
            } else {
                $question = new QuestionaireQuestion();
                $question->setReference($reference);
                $question->setUsertoken($usertoken);
            }

            $question->setName('');
            if ($type == 'number') {
                $question->setScaleVal($answers[$i]['number']);
            } else {
                $question->setAnswerText($answertext);
            }
            $question->setDescription($formId);
            $entityManager->persist($question);
            $entityManager->flush();


        }


        return new Response('Saved');
    }
}
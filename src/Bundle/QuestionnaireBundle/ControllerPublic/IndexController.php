<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\ControllerPublic;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionOverride;
use Trollfjord\Bundle\QuestionnaireBundle\Service\TypeFormService;
use Trollfjord\Core\Controller\AbstractPublicController;
use Trollfjord\Service\CacheQuestionnaireMediaService;

/**
 * Class IndexController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\ControllerPublic
 *
 * @Route("/QuestionnairePublic", name="questionnaire_public_")
 */
class IndexController extends AbstractPublicController
{
    /**
     * @Route("/form/{typeFormId}", name="form")
     * @param string $typeFormId
     * @param TypeFormService $typeFormService
     * @return Response
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function form(string $typeFormId, TypeFormService $typeFormService): Response
    {
        $typeFormService->createOrUpdateForm($typeFormId);
        return new Response('SAVED!');
    }

    /**
     * @Route("/result", name="result")
     * @param TypeFormService $typeFormService
     * @param Request $request
     * @return Response
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function result(TypeFormService $typeFormService, Request $request): Response
    {
        /*
                $content = '{
          "event_id": "01FE3D6ER19X78Y7MGSN6YR1C3",
          "event_type": "form_response",
          "form_response": {
            "form_id": "zdCTt4aG",
            "token": "ka5lpiifoaef2cika5nibmodj38lam3g",
            "landed_at": "2021-08-27T08:55:38Z",
            "submitted_at": "2021-08-27T08:56:35Z",
            "hidden": {
              "usertoken": "E9TD-V61D"
            },
            "definition": {
              "id": "zdCTt4aG",
              "title": "1.1 Werte und Kultur",
              "fields": [
                {
                  "id": "r2oLmnT3umfM",
                  "title": "Wir haben innerhalb unserer Schulgemeinschaft Werte definiert, die das Lehren und Lernen an unserer Schule prägen sollen.",
                  "type": "opinion_scale",
                  "ref": "01FCWZ7Z2BZ2RT351FAG2VFBTC",
                  "properties": {}
                },
                {
                  "id": "hBgK3GMVQiZD",
                  "title": "Die Werte, die dem Lehren und Lernen an unserer Schule zugrunde liegen, sind leitend für das Handeln der Mitglieder der Schulgemeinschaft.",
                  "type": "opinion_scale",
                  "ref": "e77a8a4e-b22f-4374-9eec-17711de69c52",
                  "properties": {}
                },
                {
                  "id": "WvaIJNIyxzI5",
                  "title": "Durch die Lehr- und Lernkultur an unserer Schule sorgen wir dafür, dass Lernende die Kompetenzen ausprägen können, die sie in einer digital geprägten Gesellschaft benötigen.",
                  "type": "opinion_scale",
                  "ref": "0e1a11a1-da37-4746-bc67-2d6891ddc517",
                  "properties": {}
                },
                {
                  "id": "N6y1Q505Cryq",
                  "title": "Folgende Werte sind besonders prägend für die Vision unserer Schule:",
                  "type": "multiple_choice",
                  "allow_multiple_selections": true,
                  "allow_other_choice": true,
                  "ref": "3508ecdf-3faf-43a0-9543-3322831efa8a",
                  "properties": {},
                  "choices": [
                    {
                      "id": "1SKKaSiWWdVe",
                      "label": "Partizipation"
                    },
                    {
                      "id": "6YcUnhG7G6Cd",
                      "label": "Innovation"
                    },
                    {
                      "id": "63nj2d0SfXsK",
                      "label": "Agilität"
                    },
                    {
                      "id": "1x9ITqVWQHb0",
                      "label": "Kollaboration"
                    },
                    {
                      "id": "a1yZ1VH07rhP",
                      "label": "Nachhaltigkeit"
                    },
                    {
                      "id": "PTiWFlui49ir",
                      "label": "Diversität"
                    },
                    {
                      "id": "WnMoFmfyYSlR",
                      "label": "Demokratie"
                    }
                  ]
                },
                {
                  "id": "cIjv71cE8OlV",
                  "title": "Die Werte unserer Schule bilden die Grundlage für die Zusammenarbeit zwischen …",
                  "type": "multiple_choice",
                  "allow_multiple_selections": true,
                  "ref": "81e91b2e-d6f2-41ff-8d12-6aebc92c6814",
                  "properties": {},
                  "choices": [
                    {
                      "id": "wmDJ7y8oFCSp",
                      "label": "Lernenden."
                    },
                    {
                      "id": "JoUajU5SUQ0N",
                      "label": "Schulleitung."
                    },
                    {
                      "id": "xOqd0kVaEWWQ",
                      "label": "erweiterte Schulleitung."
                    },
                    {
                      "id": "35oYCV2e8w8f",
                      "label": "Lehrkräften."
                    },
                    {
                      "id": "YKSN45NdsPtJ",
                      "label": "sonstigem pädagogischen Personal."
                    },
                    {
                      "id": "joIJvGhbeKB8",
                      "label": "Eltern."
                    },
                    {
                      "id": "aoZl4h3FyCJv",
                      "label": "Schulaufsicht."
                    }
                  ]
                },
                {
                  "id": "hemUSMTNUjw2",
                  "title": "Folgende Kompetenzen der Lernenden sollen durch die Lehr- und Lernkultur unserer Schule besonders gefördert werden:",
                  "type": "multiple_choice",
                  "allow_multiple_selections": true,
                  "allow_other_choice": true,
                  "ref": "9ed0f7e6-22ef-43c9-983c-320f2185d02c",
                  "properties": {},
                  "choices": [
                    {
                      "id": "MzoM9axANeOk",
                      "label": "kollaboratives Arbeiten"
                    },
                    {
                      "id": "6KAY6vqWlo57",
                      "label": "offene und respektvolle Kommunikation"
                    },
                    {
                      "id": "SuYoIiQunU8o",
                      "label": "kritisches Denken"
                    },
                    {
                      "id": "tSapeKHgh02B",
                      "label": "Kreativität"
                    },
                    {
                      "id": "tgpMm1ytNeVE",
                      "label": "Problemlösung"
                    },
                    {
                      "id": "8YBWkKZjQ6YK",
                      "label": "Resilienz"
                    },
                    {
                      "id": "mI6oAFDxRgd0",
                      "label": "Flexibilität"
                    }
                  ]
                },
                {
                  "id": "fpdXeEddgNHe",
                  "title": "Welche Werte sind in einer digital geprägten Gesellschaft besonders relevant und sollten in der Vision Ihrer Schule berücksichtigt werden? Halten Sie Ihre Überlegungen dazu fest.",
                  "type": "long_text",
                  "ref": "2c4bb827-9e7f-47a8-a538-7445972bbd3b",
                  "properties": {}
                },
                {
                  "id": "RRLFoW6ZJHeW",
                  "title": "Schultransform wird in Zukunft eine Verknüpfung zwischen Schulen und Schulträgern ermöglichen. Sind Sie einverstanden, dass ihre Notizen anonym geteilt werden?",
                  "type": "multiple_choice",
                  "ref": "5e77ad8b-420f-4584-ba6d-e9800ed49781",
                  "properties": {},
                  "choices": [
                    {
                      "id": "YTfq6JTvCl7j",
                      "label": "Ich bin einverstanden, dass meine Notizen anonym mit unserem Schulträger geteilt werden sobald diese Funktion im Selbstcheck-Werkzeug verfügbar ist."
                    },
                    {
                      "id": "KWdekjziWJh0",
                      "label": "Ich bin einverstanden, dass meine Notizen anonym mit dem Entwicklungsteam von Schultransform zur Evaluation und Weiterentwicklung geteilt werden."
                    }
                  ]
                },
                {
                  "id": "LmrztjALjgVr",
                  "title": "Wie hat Ihnen der Fragebogen gefallen?",
                  "type": "rating",
                  "ref": "a2ef3c91-1fd6-4f1d-9b71-9839ff05318a",
                  "properties": {}
                }
              ]
            },
            "answers": [
              {
                "type": "number",
                "number": 4,
                "field": {
                  "id": "r2oLmnT3umfM",
                  "type": "opinion_scale",
                  "ref": "01FCWZ7Z2BZ2RT351FAG2VFBTC"
                }
              },
              {
                "type": "number",
                "number": 3,
                "field": {
                  "id": "hBgK3GMVQiZD",
                  "type": "opinion_scale",
                  "ref": "e77a8a4e-b22f-4374-9eec-17711de69c52"
                }
              },
              {
                "type": "number",
                "number": 5,
                "field": {
                  "id": "WvaIJNIyxzI5",
                  "type": "opinion_scale",
                  "ref": "0e1a11a1-da37-4746-bc67-2d6891ddc517"
                }
              },
              {
                "type": "choices",
                "choices": {
                  "labels": [
                    "Partizipation",
                    "Agilität"
                  ],
                  "other": "Meine andere Antwort"
                },
                "field": {
                  "id": "N6y1Q505Cryq",
                  "type": "multiple_choice",
                  "ref": "3508ecdf-3faf-43a0-9543-3322831efa8a"
                }
              },
              {
                "type": "choices",
                "choices": {
                  "labels": [
                    "sonstigem pädagogischen Personal.",
                    "erweiterte Schulleitung."
                  ]
                },
                "field": {
                  "id": "cIjv71cE8OlV",
                  "type": "multiple_choice",
                  "ref": "81e91b2e-d6f2-41ff-8d12-6aebc92c6814"
                }
              },
              {
                "type": "choices",
                "choices": {
                  "labels": [
                    "Resilienz",
                    "offene und respektvolle Kommunikation"
                  ]
                },
                "field": {
                  "id": "hemUSMTNUjw2",
                  "type": "multiple_choice",
                  "ref": "9ed0f7e6-22ef-43c9-983c-320f2185d02c"
                }
              },
              {
                "type": "text",
                "text": "Meine tolle Antwort",
                "field": {
                  "id": "fpdXeEddgNHe",
                  "type": "long_text",
                  "ref": "2c4bb827-9e7f-47a8-a538-7445972bbd3b"
                }
              },
              {
                "type": "choice",
                "choice": {
                  "label": "Ich bin einverstanden, dass meine Notizen anonym mit unserem Schulträger geteilt werden sobald diese Funktion im Selbstcheck-Werkzeug verfügbar ist."
                },
                "field": {
                  "id": "RRLFoW6ZJHeW",
                  "type": "multiple_choice",
                  "ref": "5e77ad8b-420f-4584-ba6d-e9800ed49781"
                }
              },
              {
                "type": "number",
                "number": 4,
                "field": {
                  "id": "LmrztjALjgVr",
                  "type": "rating",
                  "ref": "a2ef3c91-1fd6-4f1d-9b71-9839ff05318a"
                }
              }
            ]
          }
        }';*/
        //$result = json_decode($content, true);

        // Disable TypeForm-Result/Fragebogen Import
        //$result = json_decode($request->getContent(), true);
        //$typeFormService->saveResult($result);
        return new Response('OK');
    }

    /**
     * @Route("/test", name="test")
     * @throws InvalidArgumentException
     */
    public function test(CacheQuestionnaireMediaService $cacheQuestionnaireMediaService)
    {

        $cacheQuestionnaireMediaService->cacheQuestionnaires();
        exit;

    }

    /**
     * @Route("/get-question/{questionId<\d+>?}", name="get_question")
     */
    public function getQuestionById(int $questionId, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $success = false;
        $question = null;

        //check credentials name: 'trollfjord_1ab257Jz' entry: 'dkeirufjZtegd42Lksj'
        if ($request->query->get('name', null) === 'trollfjord_1ab257Jz'
            && $request->query->get('key', null) === 'dkeirufjZtegd42Lksj') {
            if ($questionRaw = $entityManager->getRepository(Question::class)->find($questionId)) {
                $success = true;
                $question = [
                    'id' => $questionRaw->getId(),
                    'question' => $questionRaw->getQuestion(),
                    'override' => $questionRaw->getOverrides()->toArray(),
                ];
            }
        } else {

        }

        return new JsonResponse(['success' => $success, 'question' => $question]);

    }

    /**
     * @Route("/get-questions", name="get_questions")
     */
    public function getQuestions(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $success = false;
        $questions = null;

        //check credentials name: 'trollfjord_1ab257Jz' entry: 'dkeirufjZtegd42Lksj'
        if ($request->query->get('name', null) === 'trollfjord_1ab257Jz'
            && $request->query->get('key', null) === 'dkeirufjZtegd42Lksj') {
            if ($questionsRaw = $entityManager->getRepository(Question::class)->findAll()) {
                $questions =  [];
                foreach ($questionsRaw as $questionObj) {
                    $questions[] = [
                        'id' => $questionObj->getId(),
                        'question' => $questionObj->getQuestion(),
                        'override' => $questionObj->getOverrides()->toArray(),
                    ];
                }
                $success = true;
            }
        } else {

        }

        return new JsonResponse(['success' => $success, 'question' => $questions]);

    }

    /**
     * @Route("/get-question-overrides/{questionId<\d+>?}", name="get_question_overrides")
     */
    public function getQuestionsOverrides(int $questionId, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $success = false;
        $questionOverride = null;

        //check credentials name: 'trollfjord_1ab257Jz' entry: 'dkeirufjZtegd42Lksj'
        if ($request->query->get('name', null) === 'trollfjord_1ab257Jz'
            && $request->query->get('key', null) === 'dkeirufjZtegd42Lksj') {
            if ($questionOverride = $entityManager->getRepository(QuestionOverride::class)->findBy(['override' => $questionId])) {
                $success = true;
            }
        } else {

        }
        return new JsonResponse(['success' => $success, 'question' => $questionOverride]);

    }
}

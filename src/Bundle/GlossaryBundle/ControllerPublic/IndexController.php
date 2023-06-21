<?php

namespace Trollfjord\Bundle\GlossaryBundle\ControllerPublic;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trollfjord\Bundle\GlossaryBundle\Entity\Glossary;
use Trollfjord\Bundle\GlossaryBundle\Service\GlossaryService;
use Trollfjord\Core\Controller\AbstractPublicController;
use Symfony\Component\Routing\Annotation\Route;


/**
 * /**
 * Class IndexController
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\GlossaryBundle\ControllerPublic
 *
 * @Route("/Glossar", name="glossar_")
 */
class IndexController extends AbstractPublicController
{

    /**
     * @var string
     */
    private string $glossaryTemplate = "";
    private int $glossaryPaginatorFront;

    public function __construct(string $glossaryTemplate, int $glossaryPaginatorFront)
    {
        $this->glossaryTemplate = $glossaryTemplate;
        $this->glossaryPaginatorFront = $glossaryPaginatorFront;
    }

    /**
     * @Route("/{letter}/{slug}", name="home", defaults={"letter": null, "slug": null})
     * @param string|null $letter
     * @param string|null $slug
     * @param GlossaryService $glossaryService
     * @param Request $request
     * @return Response
     */
    public function index(?string $letter, ?string $slug, GlossaryService $glossaryService, Request $request): Response
    {


        $paginationPerPage = $this->glossaryPaginatorFront;

        if (isset($letter)) {
            $letter = ucfirst($letter);

        }
        $wordNotFound = null;
        $offset = null;
        if (!$wordFound = $glossaryService->getWordBySlug($slug)) {
            $wordNotFound = $slug;
            $word = null;
        }

        $listOfWords = null;
        $letterToShow = null;
        $paginator = null;
        $next = null;
        if (isset($letter)) {
            $letterChecker = $glossaryService->checkIfValidLetter($letter);
            $valid = false;
            if (!empty($letterChecker)) {
                $valid = true;
            }
            $letterToShow = [
                'valid' => $valid,
                'value' => $letterChecker,
                'numberOfChars' => strlen($letter),
                'rawValue' => $letter,
            ];

            if (!isset($slug)) {
                if ($letter && $letterChecker) {
                    //retrieve the list of words from the database
                    // $listOfWords = $glossaryService->getWordsByGroupLetterId($letter);
                    $offset = max(0, $request->query->getInt('offset', 0));
                    $letterGroup = $glossaryService->getLetterGroup($letter);
                    $paginator = $glossaryService->getPaginator($letterGroup, $offset, $paginationPerPage);
                    $next = min(count($paginator), $offset + $paginationPerPage);
                }
            }
        }

        $letters = $glossaryService->getAlphabetLetters();

        $arrayToReturn = [
            'letters' => $letters,
            'listOfWords' => $paginator,
            'word' => $wordFound,
            'wordNotFound' => $wordNotFound,
            'letterToShow' => $letterToShow,
            'previous' => $offset - $paginationPerPage,
            'next' => $next,
        ];
        return $this->render($this->glossaryTemplate, $arrayToReturn);
    }

    /**
     * @Route("/word/id/{id<\d+>?}", name="get_word")
     *
     */
    public function getWordById(?Glossary $word, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['data' => $word]);
        } else {
            return $this->redirectToRoute('glossar_home');
        }
    }


}

<?php

namespace Trollfjord\Bundle\GlossaryBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\GlossaryBundle\Entity\Glossary;
use Trollfjord\Bundle\GlossaryBundle\Form\GlossaryWord;
use Trollfjord\Bundle\GlossaryBundle\Repository\GlossaryRepository;
use Trollfjord\Bundle\GlossaryBundle\Service\GlossaryService;
use Trollfjord\Bundle\GlossaryBundle\Service\SlugifyService;
use Trollfjord\Core\Controller\AbstractController;


/**
 * Class IndexController
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\GlossaryBundle\Controller
 *
 * @Route("/Glossary", name="glossary_")
 */
class IndexController extends AbstractController
{
    /**
     * @var int
     */
    private int $itemsPerPage;

    public function __construct(int $itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * @Route("/{letter}", name="home", defaults={"letter"=null})
     * @param string|null $letter
     * @param Request $request
     * @param GlossaryService $service
     * @return Response
     */
    public function index(?string $letter, Request $request, GlossaryService $service)
    {
        if ($request->isXmlHttpRequest()) {

            //find the letter group id
            if (in_array($letter, $service->getAlphabet())) {
                $groupLetterId = array_search($letter, $service->getAlphabet());

                /** @var GlossaryRepository $gr */
                $gr = $this->getDoctrine()->getRepository(Glossary::class);
                return new JsonResponse(
                    $gr->find4Ajax(
                        $groupLetterId,
                        $request->query->getAlnum('sort', 'position'),
                        $request->query->getBoolean('sortDesc', false),
                        $request->query->getInt('page', 1),
                        $request->query->getInt('size', 1),
                    ));
            }
        }
        $letters = $service->getAlphabetLetters();
        return $this->render('@Glossary/index/index.html.twig', [
            'letters' => $letters,
            'itemsPerPage' => $this->itemsPerPage ?: 5,
        ]);
    }

    /**
     * @Route("/ajax/list", name="ajax_list")
     * @param GlossaryService $service
     * @return JsonResponse
     */
    public function ajaxL(GlossaryService $service): JsonResponse
    {
        return new JsonResponse($service->getAlphabetLetters());
    }

    /**
     * @Route("/word/new", name="new_word")
     * @param Request $request
     * @param GlossaryService $service
     * @return Response
     */
    public function createWordIframe(Request $request, GlossaryService $service): Response
    {

        //this part is going to show the form for saving a new word
        $glossary = new Glossary();
        $form = $this->createForm(GlossaryWord::class, $glossary, []);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $word = $form->getData();
            $firstLetter = utf8_decode($word->getWord());
            $firstLetter = utf8_encode($firstLetter[0]);
            //helper to set the group id base on the letter
            $groupId = $service->getGroupId($firstLetter);
            $word->setLetterGroup($groupId);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($word);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Wort erfolgreich gespeichert'
            );
            return $this->render('@Glossary/index/close-frame.html.twig', [
                'newWordId' => $word->getId(),
            ]);
        }

        return $this->render('@Glossary/index/create-word-iframe.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/words/{letter}", name="words_" , defaults={"letter"=null})
     * @param string $letter
     * @param Request $request
     * @param GlossaryService $service
     * @return JsonResponse
     */
    public function list(string $letter, Request $request, GlossaryService $service): JsonResponse
    {


        if ($request->isXmlHttpRequest()) {
            //find the letter group id
            if (in_array($letter, $service->getAlphabet())) {
                $groupLetterId = array_search($letter, $service->getAlphabet());
                /** @var GlossaryRepository $gr */
                $gr = $this->getDoctrine()->getRepository(Glossary::class);

                $data = $gr->find4Ajax(
                    $groupLetterId,
                    $request->query->getAlnum('sort', 'position'),
                    $request->query->getBoolean('sortDesc', false),
                    $request->query->getInt('page', 1),
                    $request->query->getInt('size', 1),
                );

                return new JsonResponse($data);
            }


        }
        return new JsonResponse(
            ['items' => false],
        );
    }

    /**
     * @Route("/word/edit/{id}", name="edit_word_iframe", defaults={"id"=null})
     * @param int $id
     * @param Request $request
     * @param GlossaryService $service
     * @return Response
     */
    public function editIframe(int $id, Request $request, GlossaryService $service): Response
    {
        $glossaryData = $service->getById($id);
        $form = $this->createForm(GlossaryWord::class, $glossaryData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $wordData = $form->getData();
            $firstLetter = utf8_decode($wordData->getWord());
            $firstLetter = utf8_encode($firstLetter[0]);

            //helper to set the group id base on the letter
            $groupId = $service->getGroupId($firstLetter);

            $wordData->setLetterGroup($groupId);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($wordData);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Wort erfolgreich gespeichert'
            );


            $returnWordId = $wordData->getId() ? $wordData->getId() : null;
            return $this->render('@Glossary/index/close-frame.html.twig', [
                'newWord' => null,
                'updateWordId' => $returnWordId,
            ]);
        }


        return $this->render('@Glossary/index/edit-word-iframe.html.twig', [
            'form' => $form->createView(),
            'word' => $glossaryData->getWord(),
        ]);

    }

    /**
     * @Route("/delete/{id}/{word}", name="delete")
     *
     * */
    public function deleteWord(int $id, string $word, GlossaryService $service): JsonResponse
    {

        $success = false;
        $wordDeleted = 'Fail to delete';


        try {
            if ($wordDeleted = $service->deleteWordById($id)) {
                $success = true;
                $wordDeleted = $id;
            }

        } catch (Exception $exception) {


        }
        return new JsonResponse([
                'success' => $success,
                'word' => $wordDeleted
            ]
        );

    }

    /**
     * @Route("/word/search/{word}", name="search" , defaults={"word": null})
     *
     * */
    public function searchWord(?string $word, GlossaryService $service): JsonResponse
    {
        $success = false;
        $wordFound = false;

        try {
            if ($wordFound = $service->searchWordInTable($word)) {
                $success = true;
            }
        } catch (Exception $exception) {
        }
        return new JsonResponse([
                'success' => $success,
                'word' => $wordFound
            ]
        );
    }

    /**
     * @Route("/word/search/id/{wordId}", name="search_by_id" , defaults={"wordId": null})
     *
     * */
    public function searchWordById(?int $wordId, GlossaryService $service): JsonResponse
    {
        $success = false;
        $wordFound = false;


        if ($wordFound = $service->getWord($wordId)) {
            $success = true;
        }

        return new JsonResponse([
                'success' => $success,
                'word' => [$wordFound]
            ]
        );
    }

    /**
     * @Route("/word/autocomplete/{word}", name="search_autocomplete", defaults={"word": null})
     *
     * */
    public function searchWordAutocomplete(?string $word, GlossaryService $service, Request $request): JsonResponse
    {

        $success = false;
        $wordsFound = false;

        try {
            if ($wordsFound = $service->searchWordInTableAutocomplete($word)) {
                $success = true;
            }

        } catch (Exception $exception) {


        }
        return new JsonResponse([
                'success' => $success,
                'word' => $wordsFound
            ]
        );

    }

    /**
     * @Route("/word/slugify/", name="slugify", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param SlugifyService $slugifyService
     * @return JsonResponse
     */
    public function slugify(Request $request, SlugifyService $slugifyService): JsonResponse
    {
        $slug = $slugifyService::slugify($request->query->get('word'));
        return new JsonResponse($slug === null ? "" : $slug);
    }


}
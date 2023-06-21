<?php

namespace Trollfjord\Bundle\GlossaryBundle\Service;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\PreRenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use Trollfjord\Bundle\GlossaryBundle\Entity\Glossary;
use Trollfjord\Bundle\GlossaryBundle\Repository\GlossaryRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class GlossaryService implements PreRenderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var GlossaryRepository
     */
    private GlossaryRepository $repository;

    private array $alphabet = ['toUnset', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ae', 'Oe', 'Ue'];

    /**
     * GlossaryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param GlossaryRepository $repository
     * @param RegexService $regexService
     */
    public function __construct(RegexServiceInterface $regexService, EntityManagerInterface $entityManager, GlossaryRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->regexService = $regexService;
        unset($this->alphabet[0]);
    }

    /**
     * @return array
     */
    public function getAlphabet(): array
    {
        return $this->alphabet;
    }

    /**
     * @param string|null $content
     * @return string|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function applyGlossary(?string $content): ?string
    {
        try {
            return $this->regexService->applyGlossary($content);
        } catch (Exception $e) {
        }
        return false;
    }

    public function getLetterGroup(string $letter)
    {
        return array_search($letter, $this->alphabet);
    }

    /**
     * @return array
     */
    public function getAlphabetLetters(): array
    {


        //get the list of letters with the id($key)
        $arrayToReturn = [];

        foreach ($this->alphabet as $key => $value) {
            //get if the letter group has items(words)

            $numberOfWords = count($this->getWordsByGroupLetterId($value));


            $toDisplay = $this->translateLetter($value);
            $arrayToReturn[$key] = [
                'key' => $key,
                'value' => $value,
                'display' => $toDisplay,
                'numberOfWords' => $numberOfWords,
            ];
        }

        return $arrayToReturn;

    }

    /**
     * @param string $letter
     * @return array
     */
    public function getWordsByGroupLetterId(string $letter): array
    {
        //translate the letter
        //check if letter in array
        if (in_array($letter, $this->alphabet)) {
            $groupLetterId = array_search($letter, $this->alphabet);


            return $this->entityManager->createQueryBuilder()
                ->select('g')
                ->from(Glossary::class, 'g')
                ->where('g.letterGroup =  :gId')
                ->setParameters(['gId' => $groupLetterId])
                ->getQuery()
                ->getArrayResult();
        }

        return [];

    }

    /**
     * @param $letter
     * @return string
     */
    private function translateLetter($letter): string
    {

        if ($letter === 'Ae') {
            $letter = 'Ä';
        }
        if ($letter === 'Ue') {
            $letter = 'Ü';
        }
        if ($letter === 'Oe') {
            $letter = 'Ö';
        }
        if ($letter === 'Ss') {
            $letter = 'ß';
        }

        return $letter;
    }

    public function getById(int $id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param int $letterGroup
     * @param int $offset
     * @param int $itemsPerPage
     * @return Paginator
     */
    public function getPaginator(int $letterGroup, int $offset, int $itemsPerPage): Paginator
    {
        return $this->repository->getCommentPaginator($letterGroup, $offset, $itemsPerPage);
    }


    /**
     * @param $firstLetter
     * @return false|int|string
     */
    public function getGroupId($firstLetter)
    {
        $firstLetter = strtoupper($firstLetter);
        $translationFirstSteep = [
            'Ä',
            'Ö',
            'Ü',
            'ß',
            'ä',
            'ö',
            'ü',
        ];

        $translation = [
            'Ä' => 'Ae',
            'Ö' => 'Oe',
            'Ü' => 'Ue',
            'ß' => 'Ss',
            'ä' => 'Ae',
            'ö' => 'Oe',
            'ü' => 'Ue',
        ];
        if (in_array($firstLetter, $translationFirstSteep)) {
            $firstLetter = $translation[$firstLetter];
        }
        return array_search($firstLetter, $this->alphabet);

    }


    public function checkIfValidLetter($letter)
    {
        $toReturn = false;
        if (in_array($letter, $this->alphabet)) {
            $toReturn = $this->translateLetter($letter);
        }
        return $toReturn;
    }

    /**
     * @param string $renderMode
     * @param string|null $content
     * @return string|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function preRenderContent(string $renderMode, ?string $content): ?string
    {
        if ($renderMode === TemplateEngine::RENDER_MODE_PUBLIC) {
            try {
                return $this->applyGlossary($content);
            } catch (Exception $exception) {
            }

        }
        return $content;
    }

    /**
     * @param $wordId
     * @return Glossary
     */
    public function getWord($wordId): Glossary
    {
        return $this->repository->find($wordId);
    }

    /**
     * @param $slug
     * @return Glossary | null
     */
    public function getWordBySlug($slug): ?Glossary
    {
        return $this->repository->findOneBy(['slug' => $slug]);
    }


    public function deleteWordById($wordId)
    {
        $wordToReturn = false;
        $wordToDelete = $this->getWord($wordId);
        if ($wordToDelete) {
            $this->entityManager->remove($wordToDelete);
            $this->entityManager->flush();
            $wordToReturn = $wordToDelete->getWord();
        }

        return $wordToReturn;

    }

    /**
     * @param $word
     * @return array
     */
    public function searchWordInTable($word): array
    {
        return $this->repository->searchWord($word);
    }

    /**
     * @param $word
     * @return array
     */
    public function searchWordInTableAutocomplete($word): array
    {
        return $this->repository->searchWordAutocompleteRaw($word);
    }

}
<?php

namespace Trollfjord\Bundle\GlossaryBundle\Service;

use Trollfjord\Bundle\GlossaryBundle\Repository\GlossaryRepository;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class RegexService implements RegexServiceInterface
{
    /**
     * @var GlossaryRepository
     */
    private GlossaryRepository $repository;

    /**
     * @var Environment
     */
    private Environment $twig;

    /**
     * @var string
     */
    private string $twigTemplate;

    /**
     * @var array|string[]
     */
    private array $alphabet = ['toUnset', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ae', 'Oe', 'Ue'];

    public function __construct(string $twigTemplate, GlossaryRepository $repository, Environment $twig)
    {
        $this->repository = $repository;
        $this->twig = $twig;
        $this->twigTemplate = $twigTemplate;
        unset($this->alphabet[0]);
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function applyGlossary(?string $content): ?string
    {
        /*
        Process of the glossary replacement function:

        1) Check if the glossary tags are found in the text if not found return the original content.
        2) For every match block found set a placeholder on the original content of the site.
        3) Retrieve all words on the glossary table.
        4) Build an array with all parent and children words that are in the glossary table.
        5) Loop every word (glossary table words: parent & children) and do the replacement with the template.
        6) Delete the glossary tags, placeholders and return the content.
        */

        /* Step 1 */
        $regex = '/(?s)(<!--glossary-start--\>)(.*?)(<!--glossary-end--\>)/';
        $glossaryFound = preg_match_all($regex, $content, $matches);
        //return the original content if was not matches found
        if (!$glossaryFound) {
            return $content;
        }

        /* Step 2 */
        //content to work with: array
        $contentToWork = $matches[0];

        //define the number of block found on the content of the page(<!--glossary-start--><!--glossary-end-->)
        //create an array with the number of replacements
        if (!empty($contentToWork)) {
            $numberOfMatches = count($contentToWork);
            for ($i = 0; $i <= $numberOfMatches; $i++) {
                $placeHolder = "GlossaryPlaceholder" . $i;
                //replace the content with a placeholder: 'GlossaryPlaceholder' -> using an array
                $content = preg_replace($regex, $placeHolder, $content, 1);
            }
        }

        /* Step 3 */
        //retrieve the words from the glossary table
        $dataFromTableGlossary = $this->repository->findAll();

        //retrieve all words in the 'Glossary' entity and insert them into an array
        // in order to make just one call to the DB
        $wordsInDb = [];


        /* Step 4 */
        //prepare the words that are in the glossary table
        //and their children words(related) and make and array
        //in order to match against each matched content $contentToWork
        foreach ($dataFromTableGlossary as $key => $item) {
            $word = $item->getWord();
            if (empty($word)) {
                continue;
            }
            $wordsInDb[$word] = [

                'key' => $key,
                'original' => $word,
                'isRelatedWord' => false,

            ];
            if ($this->isGermanFirstLetter($word)) {
                //make the word upper or lower
                $germanLetterChanged = $this->doUpperOrLowerGermanLetter($word);
                $wordsInDb[$germanLetterChanged] = [
                    'key' => $key,
                    'original' => $word,
                    'isRelatedWord' => false,
                ];
            } else {
                //find out if the first letter is upper
                if (ctype_upper($word[0])) {
                    //make the first lower
                    $wordsInDb[lcfirst($word)] = [
                        'key' => $key,
                        'original' => $word,
                        'isRelatedWord' => false,
                    ];
                } else {
                    //if lower case
                    //make the first letter upper
                    $wordsInDb[ucfirst($word)] = [
                        'key' => $key,
                        'original' => $word,
                        'isRelatedWord' => false,
                    ];
                }
            }
            if ($item->getRelatedWords() != "") {
                $relatedWordsInDb = preg_split('/,/', $item->getRelatedWords(), NULL, PREG_SPLIT_NO_EMPTY);

                foreach ($relatedWordsInDb as $key2 => $wordRel) {
                    //german letter in the first character
                    $wordRel = trim($wordRel);
                    $wordsInDb[$wordRel] = [
                        'key' => $key,
                        'original' => $wordRel,
                        'isRelatedWord' => true,
                    ];
                    if ($this->isGermanFirstLetter($wordRel)) {
                        //make the word upper or lower
                        $germanLetterChanged = $this->doUpperOrLowerGermanLetter($wordRel);
                        $wordsInDb[$germanLetterChanged] = [
                            'key' => $key,
                            'original' => $wordRel,
                            'isRelatedWord' => true,
                        ];
                    } else {
                        //find out if the first letter is upper
                        if (ctype_upper($wordRel[0])) {
                            //make the first lower
                            $wordsInDb[lcfirst($wordRel)] = [
                                'key' => $key,
                                'original' => $wordRel,
                                'isRelatedWord' => true,
                            ];
                        } else {
                            //if lower case
                            //make the first letter upper
                            $wordsInDb[ucfirst($wordRel)] = [
                                'key' => $key,
                                'original' => $wordRel,
                                'isRelatedWord' => true,
                            ];
                        }
                    }
                }

            }
        }

        /* Step 5 */
        /*MAIN FUNCTION TO REPLACE THE WORDS FOUNT ON THE CONTENT OF THE SITE WITH THE TEMPLATE*/
        //LOOP THROUGH ALL WORDS ON THE GLOSSARY TABLE AND ALSO OVER THE BLOCKS WITH THE GLOSSARY TAGS
        foreach ($wordsInDb as $wordAsKey => $wordArray) {
            //find the index of the parent word
            $hasMatch = false;
            $patter = "/(?<!([a-zA-Z]))($wordAsKey)\b/";

            //looping all matching blocks found on the content comming from the page
            //and return this to the original text on the right position
            foreach ($contentToWork as $key => $contentItem) {
                $contentItem = trim($contentItem);
                if (preg_match($patter, $contentItem)) {
                    $hasMatch = true;
                    $index = $wordArray['key'];
                    $wordObject = $dataFromTableGlossary[$index];
                    //letter group
                    if (array_key_exists($wordObject->getLetterGroup(), $this->alphabet)) {
                        $letterGroup = $this->alphabet[$wordObject->getLetterGroup()];
                    } else {
                        $letterGroup = 'SondernLetter';
                    }

                    //camouflage middle words in case that the record is a phrase.
                    $wordAsKey = preg_replace('/\s/', '_PlaceholderMiddleSpace_', $wordAsKey);

                    $word_ = $wordObject->getWord();
                    $word_ = preg_replace('/\s/', '_PlaceholderMiddleSpace_', $word_);

                    $slug_ = $wordObject->getSlug();
                    $slug_ = preg_replace('/-/', '_PlaceholderMiddleHyphen_', $slug_);

                    $href = "/Glossar/" . $letterGroup . "/" . $slug_ . "_eraseme_";

                    //data to pass to the render
                    $dataToRender = [
                        'wordDisplayed' => $wordAsKey . "_eraseme_",
                        'isRelatedWord' => $wordArray['isRelatedWord'],
                        'word' => $word_ . "_eraseme_",
                        'wordId' => $wordObject->getId(),
                        'slug' => $slug_ . "_eraseme_",
                        'image' => $wordObject->getImage(),
                        'href' => $href,
                    ];
                    $newValue = $this->twig->render($this->twigTemplate, [
                        'data' => $dataToRender,
                    ]);
                    //change the contentToWork in the content: now content has the placeholder 'GlossaryPlaceholder'
                    $contentToWork[$key] = preg_replace($patter, $newValue, $contentItem);
                }
            }
        }

        //put back the replaced content to the original contetn of the page
        //in the right order
        if (!empty($contentToWork)) {
            $numberOfMatches = count($contentToWork);
            for ($i = 0; $i < $numberOfMatches; $i++) {
                $placeHolder = "/GlossaryPlaceholder" . $i . "/";
                //replace the content with a placeholder: 'GlossaryPlaceholder' -> using an array
                $content = preg_replace($placeHolder, $contentToWork[$i], $content);
            }
        }

        /* Step 6 */
        //DELETE THE GLOSSARY TAGS AND THE PLACEHOLDERS FOR THE VARIABLES IN THE DATA TO PASS TO THE TEMPLATE
        $content = str_replace(['<!--glossary-start-->', '<!--glossary-end-->'], '', $content);
        $content = preg_replace('/_PlaceholderMiddleSpace_/', ' ', $content);
        $content = preg_replace('/_PlaceholderMiddleHyphen_/', '-', $content);
        return preg_replace('/_eraseme_/', '', $content);
    }

    /**
     * @param string $word
     * @return bool
     */
    private function isGermanFirstLetter(string $word): bool
    {
        $toReturn = false;
        $match = preg_match('/Ü|Ä|Ö|ä|ö|ü/', $word, $matches, PREG_OFFSET_CAPTURE);
        if ($match) {
            if ($matches[0][1] === 0) {
                $toReturn = true;
            }
        }
        return $toReturn;
    }

    private function doUpperOrLowerGermanLetter($word)
    {
        $toReturn = null;
        $match = preg_match('/Ü|Ä|Ö|ä|ö|ü/', $word, $matches, PREG_OFFSET_CAPTURE);
        if ($match) {
            if ($matches[0][1] === 0) {
                //word has a german letter as first letter
                $array = [
                    'Ä' => 'ä',
                    'Ö' => 'ö',
                    'Ü' => 'ü',
                    'ä' => 'Ä',
                    'ö' => 'Ö',
                    'ü' => 'Ü',
                ];
                $pattern = "/" . $matches[0][0] . "/";
                $toReturn = preg_replace($pattern, $array[$matches[0][0]], $word, 1);
            }
        }
        return $toReturn;
    }

    //DEPRECATED
    private function encodeDescription($shortDescription): string
    {
        return preg_replace("/\s/", "Description_glossary_worD", $shortDescription);
    }

}
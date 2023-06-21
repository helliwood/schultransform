<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionChoice;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\ResultAnswer;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\CategoryRepository;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\ResultRepository;
use function in_array;
use function is_array;
use function is_null;

/**
 * Class ResultService
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle
 */
class ResultService
{

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * TypeFormService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param User  $user
     * @param array $resultData
     * @throws Exception
     */
    public function saveResult(User $user, array $resultData)
    {
        $this->entityManager->beginTransaction();
        try {
            $questionnaire = $this->entityManager->find(Questionnaire::class, $resultData['questionnaireId']);
            $result = new Result();
            $result->setUser($user);
            $result->setQuestionnaire($questionnaire);
            $result->setRating($resultData['rating']);

            //If school authority questionnaire: save formal question as before share
            if (isset($resultData['formal'],$resultData['userType'])){
                //save if school authority
                if($resultData['userType'] === 'school_board'){
                    if (in_array(1, $resultData['formal'])) {
                        $result->setShareWithSchoolAuthority(true);
                    }
                    if (in_array(2, $resultData['formal'])) {
                        $result->setShareNotices(true);
                    }
                }
            }

            if (in_array(1, $resultData['share'])) {
                $result->setShare(true);
            } else {
                $result->setShare(false);
            }
            foreach ($resultData['questions'] as $questionData) {
                $question = $this->entityManager->find(Question::class, $questionData['questionId']);
                if (is_null($questionData['value'])) {
                    $resultAnswer = new ResultAnswer();
                    $resultAnswer->setType('skipped');
                    $resultAnswer->setResult($result);
                    $resultAnswer->setQuestion($question);
                    $resultAnswer->setSkipped(true);
                    if (isset($questionData['reason']) && ! empty($questionData['reason'])) {
                        $resultAnswer->setSkipReason($questionData['reason']);
                    }
                    $this->entityManager->persist($resultAnswer);
                    $result->getAnswers()->add($resultAnswer);
                } else {
                    if ($question->getChoices()->count() > 0) {
                        $values = is_array($questionData['value']) ? $questionData['value'] : [$questionData['value']];
                        foreach ($values as $value) {
                            $resultAnswer = new ResultAnswer();
                            $resultAnswer->setResult($result);
                            $resultAnswer->setQuestion($question);
                            if ($value === -1) {
                                $resultAnswer->setType('choice_other');
                                $resultAnswer->setValue($questionData['otherChoice']);
                            } else {
                                $choice = $this->entityManager->find(QuestionChoice::class, $value);
                                $resultAnswer->setType('choice');
                                $resultAnswer->setChoice($choice);
                            }
                            $this->entityManager->persist($resultAnswer);
                            $result->getAnswers()->add($resultAnswer);
                        }
                    } else {
                        $resultAnswer = new ResultAnswer();
                        $resultAnswer->setResult($result);
                        $resultAnswer->setQuestion($question);
                        $resultAnswer->setType('value');
                        $resultAnswer->setValue($questionData['value']);
                        $this->entityManager->persist($resultAnswer);
                        $result->getAnswers()->add($resultAnswer);
                    }
                }
            }
            $this->entityManager->persist($result);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param User|null $user
     * @return array
     */
    public function getResultsByUser(?User $user): array
    {
        if (is_null($user)) return [];

        /** @var ResultRepository $rr */
        $rr = $this->entityManager->getRepository(Result::class);
        /** @var CategoryRepository $cr */
        $cr = $this->entityManager->getRepository(Category::class);
        $categories = $cr->findBy(['parent' => null], ['position' => 'ASC']);
        /**
         * @param Category[]|Collection $categories
         * @return array
         */
        $addResults = function ($categories) use ($rr, $user, &$addResults) {
            $return = [];
            foreach ($categories as $category) {
                $results = $rr->getResultsByUserAndCategory($user, $category);
                $return[] = [
                    'category' => $category,
                    'results' => $results,
                    'children' => $addResults($category->getChildren())
                ];
            }
            return $return;
        };
        return $addResults($categories);
    }
}
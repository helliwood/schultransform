<?php

namespace Trollfjord\Service\Dashboard;

use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Core\Security;
use Trollfjord\Bundle\PublicUserBundle\Repository\UserRepository;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\ResultAnswer;
use Trollfjord\Entity\School;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Trollfjord\Service\CacheQuestionnaireMediaService;
use Twig\Environment;

class BaseService
{

    /**
     * @var HttpClientInterface
     */
    protected HttpClientInterface $httpClient;

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * @var Security
     */
    protected Security $security;

    /**
     * @var UserRepository
     */
    protected UserRepository $userRepository;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var CacheQuestionnaireMediaService
     */
    protected CacheQuestionnaireMediaService $cacheQuestionnaireMediaService;

    private int $minValue = 4;

    /**
     * @param Environment $twig
     * @param Security $security
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @param CacheQuestionnaireMediaService $cacheQuestionnaireMediaService
     * @param HttpClientInterface $httpClient
     */
    public function __construct(Environment                    $twig,
                                Security                       $security,
                                UserRepository                 $userRepository,
                                EntityManagerInterface         $entityManager,
                                CacheQuestionnaireMediaService $cacheQuestionnaireMediaService,
                                HttpClientInterface            $httpClient)
    {
        $this->twig = $twig;
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->cacheQuestionnaireMediaService = $cacheQuestionnaireMediaService;
        $this->httpClient = $httpClient;
    }

    public function getChartQuestionnaires($school, $categoryId): array
    {
        //get all values for the questionnaires filled out
        if (!$school) {
            return [];
        }
        $schoolId = $school->getId();
        $results = $this->entityManager->getRepository(ResultAnswer::class)->getChartDataBySchoolByCategory($schoolId, $categoryId);
        $categoryMean = 0;
        $questionnaires = [];
        //only if there are two teachers
        foreach ($results as $result) {
            if ($result['users'] > 1) {
                $questionnaires[] = $result;
            }

        }

        return $questionnaires;
    }

    public function getMeanForAllCategoriesAllUsers($schoolId): float
    {
        $toReturn = 0.0;
        $resultAnswerRepo = $this->entityManager->getRepository(ResultAnswer::class);
        if ($results = $resultAnswerRepo->getMeanForAllCategoriesAllUsers($schoolId)) {
            //sum the values to get the category mean
            $numberOfResults = 0;
            $meanArray = [];
            //loop over questionnaires
            foreach ($results as $result) {
                //only if 2 teachers
                if ($result['users'] > 1) {
                    $meanArray[] = $result['value'];
                    $numberOfResults++;
                }
            }
            if ($numberOfResults > 0) {
                $toReturn = array_sum($meanArray) / $numberOfResults;
            }

        }
        return (float)$toReturn;

    }

    public function getTeacher($schoolid): int
    {
        $resultRepo = $this->entityManager->getRepository(Result::class);
        $teachersRelatedWithSchool = $resultRepo->getTeachersRelatedWithQuestionnairesDone($schoolid);

        return count($teachersRelatedWithSchool);
    }

    public function getNumberOfTeachersOfSchool($schoolId): int
    {

        $numberOfTeachers = 0;
        /** @var School $school * */
        if ($school = $this->entityManager->getRepository(School::class)->find($schoolId)) {
            $school->getUsers();
            $numberOfTeachers = $school->getUserCount();
        }

        return $numberOfTeachers;

    }

    public function getRecommendationsForUser($user, $category): array
    {
        $userId = $user->getId();
        $categoryId = $category->getId();
        $resultRepo = $this->entityManager->getRepository(ResultAnswer::class);
        $result = $resultRepo->getRecommendationsByUser($userId, $categoryId);
        return $result;
    }

    public function getRecommendationsForSchool($schoolId, $category): array
    {
        if (!$schoolId || !$category) {
            return [];
        }
        $categoryId = $category->getId();

        $resultRepo = $this->entityManager->getRepository(ResultAnswer::class);
        $result = $resultRepo->getRecommendationsBySchool($schoolId, $categoryId);
        return $result;
    }

    public function getRecommendationsForSchoolByQuestionnaires($schoolId, $questionnairesArray): array
    {
        $result = [];
        if (!$schoolId || !$questionnairesArray) {
            return [];
        }

        $arrayQuestionnairesIds = $this->createArrayWithIds($questionnairesArray);

        if (!empty($arrayQuestionnairesIds)) {
            $resultRepo = $this->entityManager->getRepository(ResultAnswer::class);
            $result = $resultRepo->getRecommendationsBySchoolByQuestionnaires($schoolId, $arrayQuestionnairesIds);
        }

        return $result;
    }

    public function createArrayWithIds($arrayOfItems): array
    {

        //check if the mean is less or equal 4
        $toReturn = [];
        if (!empty($arrayOfItems)) {
            foreach ($arrayOfItems as $item) {
                if ($item['value'] <= $this->minValue) {
                    $toReturn[] = $item['questionnaireId'];
                }
            }
        }

        return $toReturn;

    }

    public function getQuestionnairesByCategory($school, $categoryId): array
    {
        //get all values for the questionnaires filled out
        if (!$school) {
            return [];
        }
        $schoolId = $school->getId();
        $results = $this->entityManager->getRepository(ResultAnswer::class)->getChartDataBySchoolByCategory($schoolId, $categoryId);

        $questionnaires = [];
        //only if there are two teachers
        foreach ($results as $result) {
            if ($result['users'] > 1) {
                $questionnaires[] = $result;
            }

        }


        return $questionnaires;
    }

    /**
     * @throws \Exception
     */
    public function getResultLastDate($userId, $questionnaireId)
    {
        $now = new DateTime();
        $toReturn = $now->format('d.m.Y');
        $toReturn = 3;
        $result = $this->entityManager->getRepository(Result::class)->getLastDateResult($userId, $questionnaireId);
        if (!empty($result)) {
            if (isset($result[0]['creationDate'])) {
                $dateDb = $result[0]['creationDate'];

                if ($dateDb instanceof DateTime) {
                    $toReturn = $dateDb->format('d.m.Y');
                }


            }
        }
        return $toReturn;
    }

    public function activityresults(array $utemp, array $dates, array $userDates, $userId): array
    {
        $ActivityResults = $this->entityManager->getRepository(Result::class)->findBy(['user' => $utemp], ['id' => 'DESC']);
        $catId = $formatedDate = null;
        //get day one month before
        $earliestDate = new \DateTime();
        $earliestDate->modify('-1 month');

        //Go through all result and create Date-Array for activities
        foreach ($ActivityResults as $activity) {
            $catId = $activity->getQuestionnaire()->getCategory()->getId();

            //Take Care that we start with the earliest Date with an interaction
            $formatedDate = $activity->getCreationDate();

            if ($formatedDate < $earliestDate) {
                $earliestDate = $formatedDate;
            }
            $formatedDate = $formatedDate->format('d.m.Y');

            //Create arrays when not existing
            if (!isset($dates[$catId][$formatedDate])) {
                $dates[$catId][$formatedDate] = 0;
                $userDates[$catId][$formatedDate] = 0;
            }

            //Count up
            $dates[$catId][$formatedDate] = $dates[$catId][$formatedDate] + 1;
            if ($activity->getUser()->getId() == $userId) {
                $userDates[$catId][$formatedDate] = $userDates[$catId][$formatedDate] + 1;
            }
        }
        return [$earliestDate, $catId, $formatedDate, $dates, $userDates];
    }

    public function teachersActiveByCategory($category, $school = null): array
    {
        $toReturn = [];
        if ($school) {
            $categoryId = $category->getId();
            $schoolId = $school->getId();
            $toReturn = $this->entityManager->getRepository(Result::class)->getTeachersActiveByCategory($categoryId, $schoolId);
        }

        return $toReturn;

    }

    public function getTeachersLastActivityDateByCategory($category, $teachersCodes)
    {

        $categoryId = $category->getId();
        $toReturn = $this->entityManager->getRepository(Result::class)->getTeachersLastActivityDateByCategory($categoryId, $teachersCodes);
        if (!empty($toReturn)) {
            $toReturn = array_shift($toReturn);
        }
        return $toReturn;
    }

    /**
     * @throws Exception
     */
    public function getQuestionnaireResultBySchool($schoolId, $questionnaireId, bool $forSchoolAuthorityOnly = false): array
    {

        $sql = "SELECT u.code, 
       r.rating, 
       ra.*, 
       r.share_with_school_authority, 
       r.share_notices, 
       question.question,
       question.id AS qId, 
       recommendation.id AS recommendationId, 
       question.recommendation_id AS reco2Id
            FROM publicuserbundle_user u
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = :questionnaireId " . ($forSchoolAuthorityOnly ? ' AND share_with_school_authority = TRUE' : '') . "
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id
            INNER JOIN questionnairebundle_question AS question ON question.id = ra.question_id 
            INNER JOIN questionnairebundle_recommendation As recommendation ON  recommendation.id = question.recommendation_id
            WHERE u.school_id = :schoolId AND u.code IS NOT NULL
            GROUP BY ra.id;";

        $rawData = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'schoolId' => $schoolId,
            'questionnaireId' => $questionnaireId,
        ]);

        $result = ['questions' => [], 'results' => []];
        foreach ($rawData as $data) {
            if (!in_array($data['result_id'], $result['results'])) {
                $result['results'][] = $data['result_id'];
            }
            if (!isset($result['questions'][$data['question_id']])) {
                $result['questions'][$data['question_id']] = ['answers' => [], 'skipped' => 0, 'ratings' => [], 'choices' => [], 'values' => []];
            }
            if ($data['skipped']) {
                $result['questions'][$data['question_id']]['skipped']++;
            }
            if ($data['choice_id']) {
                if (!isset($result['questions'][$data['question_id']]['choices'][$data['choice_id']])) {
                    $result['questions'][$data['question_id']]['choices'][$data['choice_id']] = 0;
                }
                $result['questions'][$data['question_id']]['choices'][$data['choice_id']]++;
            } elseif (!is_null($data['value'])) {
                if (!isset($result['questions'][$data['question_id']]['values'][$data['value']])) {
                    $result['questions'][$data['question_id']]['values'][$data['value']] = 0;
                }
                $result['questions'][$data['question_id']]['values'][$data['value']]++;
                $result['questions'][$data['question_id']]['valuesAll'][] = $data['value'];
                $result['questions'][$data['question_id']]['recommendationId'] = $data['recommendationId'];
                $result['questions'][$data['question_id']]['questionId'] = $data['question_id'];
            }
            $result['questions'][$data['question_id']]['ratings'][] = $data['rating'];
            $result['questions'][$data['question_id']]['answers'][] = [
                'type' => $data['type'],
                'value' => $data['value'],
                'choice_id' => $data['choice_id']
            ];
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public function getQuestionnaireResultBySchoolAuthorityUser($userId, $questionnaireId, bool $forSchoolAuthorityOnly = false): array
    {

        $sql = "SELECT u.code, 
       r.rating, 
       ra.*, 
       r.share_with_school_authority, 
       r.share_notices, 
       question.question,
       question.id AS qId, 
       recommendation.id AS recommendationId, 
       question.recommendation_id AS reco2Id
            FROM publicuserbundle_user u
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = :questionnaireId " . ($forSchoolAuthorityOnly ? ' AND share_with_school_authority = TRUE' : '') . "
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id
            INNER JOIN questionnairebundle_question AS question ON question.id = ra.question_id 
            INNER JOIN questionnairebundle_recommendation As recommendation ON  recommendation.id = question.recommendation_id
            WHERE u.id = :userId
            GROUP BY ra.id;";

        $rawData = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'userId' => $userId,
            'questionnaireId' => $questionnaireId,
        ]);

        $result = ['questions' => [], 'results' => []];
        foreach ($rawData as $data) {
            if (!in_array($data['result_id'], $result['results'])) {
                $result['results'][] = $data['result_id'];
            }
            if (!isset($result['questions'][$data['question_id']])) {
                $result['questions'][$data['question_id']] = ['answers' => [], 'skipped' => 0, 'ratings' => [], 'choices' => [], 'values' => []];
            }
            if ($data['skipped']) {
                $result['questions'][$data['question_id']]['skipped']++;
            }
            if ($data['choice_id']) {
                if (!isset($result['questions'][$data['question_id']]['choices'][$data['choice_id']])) {
                    $result['questions'][$data['question_id']]['choices'][$data['choice_id']] = 0;
                }
                $result['questions'][$data['question_id']]['choices'][$data['choice_id']]++;
            } elseif (!is_null($data['value'])) {
                if (!isset($result['questions'][$data['question_id']]['values'][$data['value']])) {
                    $result['questions'][$data['question_id']]['values'][$data['value']] = 0;
                }
                $result['questions'][$data['question_id']]['values'][$data['value']]++;
                $result['questions'][$data['question_id']]['valuesAll'][] = $data['value'];
                $result['questions'][$data['question_id']]['recommendationId'] = $data['recommendationId'];
                $result['questions'][$data['question_id']]['questionId'] = $data['question_id'];
            }
            $result['questions'][$data['question_id']]['ratings'][] = $data['rating'];
            $result['questions'][$data['question_id']]['answers'][] = [
                'type' => $data['type'],
                'value' => $data['value'],
                'choice_id' => $data['choice_id']
            ];
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public function getRecommendationsAdvancedByQuestionnaire($schoolId, $questionnaireId): array
    {
        $results = $this->getQuestionnaireResultBySchool($schoolId, $questionnaireId);
        //get the first 3 questions
        //get the mean of each of one
        //match the recommendation
        $arrangedQuestions = $this->getMeanOfGroupedQuestions($results['questions']);

        //loop and check if mean <= 4
        $recommendationsRaw = [];
        $recommendations = [];
        if (!empty($arrangedQuestions)) {
            foreach ($arrangedQuestions as $arrangedQuestion) {
                if ($arrangedQuestion['mean'] > 4) {

//                    $recommendationsRaw[] = $arrangedQuestion['questionId'];
                    $recommendationsRaw[] = [
                        'recommendationId' => $arrangedQuestion['recommendationId'],
                        'questionId' => $arrangedQuestion['questionId'],
                    ];
                }
            }
        }
        if (!empty($recommendationsRaw)) {
            $questionData = [];
            foreach ($recommendationsRaw as $item) {
                $questionId = $item['questionId'];
                if ($question = $this->entityManager->getRepository(Question::class)->find($questionId)) {
                    if ($question->getAdvancedRecommendation()) {
                        $recommendationAdv = $question->getAdvancedRecommendation()->toArray();
                        //check if partner materials
                        $medias = $this->getPartnerMaterialsByQuestionId($questionnaireId, $question->getId());
                        if (!empty($medias)) {
                            //targetaudience_1 > 4
                            $recommendationAdv['partnerMedias'] = $medias['targetaudience_1'];
                        }
                        $recommendations[] = $recommendationAdv;
                    }
                }
            }

//            $questionData = $this->entityManager->getRepository(Question::class)->findBy(['id' => $recommendationsRaw]);
//            if (!empty($questionData)) {
//                foreach ($questionData as $question) {
//                    if ($question->getAdvancedRecommendation()) {
//                        $recommendationAdv = $question->getAdvancedRecommendation()->toArray();
//                        //check if partner materials
//                        $medias = $this->getPartnerMaterialsByQuestionId($questionnaireId, $question->getId());
//                        dump($recommendationsRaw);
//                        if (!empty($medias)) {
//                            //targetaudience_1 > 4
//                            $recommendationAdv['partnerMedias'] = $medias['targetaudience_1'];
//                            dump($recommendationAdv);
//                        }
//                        $recommendations[] = $recommendationAdv;
//                    }
//
//                }
//            }
        }
        return $recommendations;
    }

    /**
     * @throws Exception
     */
    public function getRecommendationsAdvancedByQuestionnaireSchoolAuthority($userId, $questionnaireId): array
    {
        $results = $this->getQuestionnaireResultBySchoolAuthorityUser($userId, $questionnaireId);
        //get the first 3 questions
        //get the mean of each of one
        //match the recommendation
        $arrangedQuestions = $this->getMeanOfGroupedQuestions($results['questions']);

        //loop and check if mean <= 4
        $recommendationsRaw = [];
        $recommendations = [];
        if (!empty($arrangedQuestions)) {
            foreach ($arrangedQuestions as $arrangedQuestion) {
                if ($arrangedQuestion['mean'] > 4) {
                    $recommendationsRaw[] = $arrangedQuestion['questionId'];
                }
            }
        }
        if (!empty($recommendationsRaw)) {
            $questionData = $this->entityManager->getRepository(Question::class)->findBy(['id' => $recommendationsRaw]);
            if (!empty($questionData)) {
                foreach ($questionData as $question) {
                    if ($question->getAdvancedRecommendation()) {
                        $recommendations[] = $question->getAdvancedRecommendation()->toArray();
                    }

                }
            }
        }
        return $recommendations;
    }

    public function getPartnerMaterialsByQuestionId($questionnaireId, $questionId)
    {

        $cacheRaw = $this->cacheQuestionnaireMediaService->cacheQuestionnaires();

        if (empty($cacheRaw)) {
            return [];
        }
        if (isset($cacheRaw)) {
            if (isset($cacheRaw[$questionnaireId])) {
                if (isset($cacheRaw[$questionnaireId]['questions'])) {
                    if (isset($cacheRaw[$questionnaireId]['questions'][$questionId])) {
                        return $cacheRaw[$questionnaireId]['questions'][$questionId]['medias'];
                    }
                }
            }

        }

        return [];

    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getPartnerMaterialsByQuestionnaire($schoolId, $questionnaire): array
    {

        $questionnaireId = $questionnaire['questionnaireId'];
        $questionnaireName = $questionnaire['questionnaireName'];
        $categoryId = $questionnaire['categoryId'];
        $results = $this->getQuestionnaireResultBySchool($schoolId, $questionnaireId);
        //get the first 3 questions
        //get the mean of each of one
        //match the recommendation
        $arrangedQuestions = $this->getMeanOfGroupedQuestions($results['questions']);

        //loop and check if mean <= 4
        $materials = [];
        //cache
        $cacheRaw = $this->cacheQuestionnaireMediaService->cacheQuestionnaires();

        if (empty($cacheRaw)) {
            return [];
        }
        $cache = $cacheRaw['data'];
        //get questionnaire data from cache
        //check if exist the data for the questionnaire

        $questionnaireData = null;
        if (isset($cache[$questionnaireId]) && !empty($cache[$questionnaireId])) {
            $questionnaireData = $cache[$questionnaireId];
        }

        if (!$questionnaireData) {
            return [];
        }

        $toReturn = [];

        if (!empty($arrangedQuestions)) {
            foreach ($arrangedQuestions as $arrangedQuestion) {
                $questionId = $arrangedQuestion['questionId'];


                //get cache material targetaudience_1
                if (isset($questionnaireData['questions']) &&
                    isset($questionnaireData['questions'][$questionId])) {
                    $questionFromCache = $questionnaireData['questions'][$questionId];
                    $medias = $questionFromCache['medias'];
                    if ($arrangedQuestion['mean'] > $this->minValue) {
                        $toReturn[] = [
                            'questionnaireId' => $questionnaireId,
                            'questionnaireName' => $questionnaireName,
                            'questionId' => $questionId,
                            'questionIdDisplay' => $categoryId . '.' . $questionnaireId . '.' . $questionId,
                            'medias' => $medias['targetaudience_1'],
                            'target' => 1,
                            'link' => $cache['link'],
                        ];
                    } else {
                        $toReturn[] = [
                            'questionnaireId' => $questionnaireId,
                            'questionnaireName' => $questionnaireName,
                            'questionId' => $questionId,
                            'questionIdDisplay' => $categoryId . '.' . $questionnaireId . '.' . $questionId,
                            'medias' => $medias['targetaudience_2'],
                            'target' => 2,
                            'link' => $cache['link'],
                        ];

                    }
                }
            }
        }
        return $toReturn;
    }

    /**
     * @throws Exception
     */
    public function getRecommendationsByQuestionnaire($schoolId, $questionnaireId): array
    {
        $results = $this->getQuestionnaireResultBySchool($schoolId, $questionnaireId);
        //get the first 3 questions
        //get the mean of each of one
        //match the recommendation
        $arrangedQuestions = $this->getMeanOfGroupedQuestions($results['questions']);

        //loop and check if mean <= 4
        $recommendationsRaw = [];
        $recommendations = [];
        if (!empty($arrangedQuestions)) {
            foreach ($arrangedQuestions as $key => $arrangedQuestion) {
                if ($arrangedQuestion['mean'] <= 4) {

                    $recommendationsRaw[] = [
                        'recommendationId' => $arrangedQuestion['recommendationId'],
                        'questionId' => $arrangedQuestion['questionId'],
                    ];
//                    $recommendationsRaw[]['recommendationId'] = $arrangedQuestion['recommendationId'];
//                    $recommendationsRaw[]['questionId'] = $arrangedQuestion['questionId'];
                }
            }
        }

        if (!empty($recommendationsRaw)) {
//            $cacheRaw = $this->cacheQuestionnaireMediaService->cacheQuestionnaires();
//            $cacheRaw = $this->cacheQuestionnaireMediaService;

            foreach ($recommendationsRaw as $item) {
                $recommendationId = $item['recommendationId'];
                $questionId = $item['questionId'];
                if ($recommendation = $this->entityManager->getRepository(Recommendation::class)->find($recommendationId)) {
                    //check if partner materials
                    $medias = $this->getPartnerMaterialsByQuestionId($questionnaireId, $questionId);
                    if (!empty($medias)) {
                        //targetaudience_2 <= 4
                        $recommendation->partnerMedias = $medias['targetaudience_2'];
                    }

                    $recommendations[] = $recommendation;
                }

            }


        }

        return $recommendations;
    }

    /**
     * @throws Exception
     */
    public function getRecommendationsByQuestionnaireSchoolAuthority($userId, $questionnaireId): array
    {
        $results = $this->getQuestionnaireResultBySchoolAuthorityUser($userId, $questionnaireId);

        //get the first 3 questions
        //get the mean of each of one
        //match the recommendation
        $arrangedQuestions = $this->getMeanOfGroupedQuestions($results['questions']);

        //loop and check if mean <= 4
        $recommendationsRaw = [];
        $recommendations = [];
        if (!empty($arrangedQuestions)) {
            foreach ($arrangedQuestions as $arrangedQuestion) {
                if ($arrangedQuestion['mean'] <= 4) {
                    $recommendationsRaw[] = $arrangedQuestion['recommendationId'];
                }
            }
        }

        if (!empty($recommendationsRaw)) {
            $recommendations = $this->entityManager->getRepository(Recommendation::class)->findBy(['id' => $recommendationsRaw]);
        }

        return $recommendations;
    }

    private function getMeanOfGroupedQuestions($questions): array
    {

        $toReturn = [];
        //get he first 3 questions
        foreach ($questions as $key => $question) {
            $mean = 0;
            $questionId = 0;
            $recommendationId = 0;
            if (isset($question['valuesAll'])) {
                $sumOfValues = array_sum($question['valuesAll']);
                $numberOfValues = count($question['valuesAll']);
                $mean = $sumOfValues / $numberOfValues;
            }
            if (isset($question['questionId'])) {
                $questionId = $question['questionId'];
            }
            if (isset($question['recommendationId'])) {
                $recommendationId = $question['recommendationId'];
            }

            $toReturn[$key] = [
                'questionId' => $questionId,
                'mean' => $mean,
                'recommendationId' => $recommendationId,
            ];


        }
        return $toReturn;
    }

    public function questionnairesCompletedAndNot($schoolId, $category): array
    {
        $categoryId = $category->getId();
        //check the number of questionnaires with two teachers in the category
        $resultRepo = $this->entityManager->getRepository(Result::class);
        $questionnairesFilledOutByCategory = $resultRepo->getQuestionnairesFilledOutByCategory($schoolId, $categoryId);


        $questionnaires = [];
        //only if there are two teachers
        foreach ($questionnairesFilledOutByCategory as $item) {
            if ($item['users'] > 1) {
                $questionnaires[] = $item;
            }

        }


        $questionnairesInCategory = $category->getTeacherQuestionnaires();

        $questionnairesNotDone = count($questionnairesInCategory) - count($questionnaires);
        $questionnairesDone = count($questionnaires);
        return [$questionnairesDone, $questionnairesNotDone];
    }

    /**
     * @throws \Exception
     */
    public function getDownloads(array $questionnairesInCategory, $questionnaire = null): array
    {
        $toReturn = [];


        if ($questionnaire) {
            foreach ($questionnairesInCategory as $item) {
                if ($item['questionnaireId'] == $questionnaire->getId()) {
                    $creationDateRaw = $item['creationDate'];
                    $creationDate_ = new DateTime($creationDateRaw);
                    $creationDate = $creationDate_->format('d.m.Y');
                    $toReturn[] = [
                        'url' => '/Questionnaire/print-school-questionnaire-result/' . $item['questionnaireId'],
                        'type' => 'download',
                        'title' => 'Ihre Momentaufnahme zum Thema: ' . $item['questionnaireName'],
                        'desc' => 'ausgefüllt am ' . $creationDate,
                        'FileText' => 'Herunterladen (PDF)',
                    ];
                    break;
                }
            }
        } else {
            foreach ($questionnairesInCategory as $item) {
                $creationDateRaw = $item['creationDate'];
                $creationDate_ = new DateTime($creationDateRaw);
                $creationDate = $creationDate_->format('d.m.Y');
                $toReturn[] = [
                    'url' => '/Questionnaire/print-school-questionnaire-result/' . $item['questionnaireId'],
                    'type' => 'download',
                    'title' => 'Ihre Momentaufnahme zum Thema: ' . $item['questionnaireName'],
                    'desc' => 'ausgefüllt am ' . $creationDate,
                    'FileText' => 'Herunterladen (PDF)',
                ];
            }
        }


        return $toReturn;

    }

    /**
     * @param array $questionnairesInCategory
     * @param null $questionnaire
     * @param int $schoolId
     * @return array
     * @throws \Exception
     */
    public function getDownloadsSchoolAuthority(array $questionnairesInCategory, int $schoolId, $questionnaire = null): array
    {
        $toReturn = [];


        if ($questionnaire) {
            foreach ($questionnairesInCategory as $item) {
                if ($item['questionnaireId'] == $questionnaire->getId()) {
                    $creationDateRaw = $item['creationDate'];
                    $creationDate_ = new DateTime($creationDateRaw);
                    $creationDate = $creationDate_->format('d.m.Y');
                    $toReturn[] = [
                        'url' => '/Questionnaire/print-school-questionnaire-result-school-authority/' . $item['questionnaireId'],
                        'type' => 'download',
                        'title' => 'Ihre Momentaufnahme zum Thema: ' . $item['questionnaireName'],
                        'desc' => 'ausgefüllt am ' . $creationDate,
                        'FileText' => 'Herunterladen (PDF)',
                    ];
                    break;
                }
            }
        } else {
            foreach ($questionnairesInCategory as $item) {
                $creationDateRaw = $item['creationDate'];
                $creationDate_ = new DateTime($creationDateRaw);
                $creationDate = $creationDate_->format('d.m.Y');
                $toReturn[] = [
                    'url' => '/Questionnaire/print-school-questionnaire-result-school-authority/' . $schoolId . "/" . $item['questionnaireId'],
                    'type' => 'download',
                    'title' => 'Ihre Momentaufnahme zum Thema: ' . $item['questionnaireName'],
                    'desc' => 'ausgefüllt am ' . $creationDate,
                    'FileText' => 'Herunterladen (PDF)',
                ];
            }
        }


        return $toReturn;

    }

    public function getAllQuestionnairesAllCategories()
    {

        //get all categories
        $categories = $this->entityManager->getRepository(Category::class)->findAll();

        $questionnairesTotalRaw = [];
        /** @var Category $category * */
        foreach ($categories as $category) {
            $questionnairesTotalRaw[] = count($category->getTeacherQuestionnaires());
        }
        return array_sum($questionnairesTotalRaw);

    }

    public function getAllQuestionnairesAllCategoriesSchoolAuthority()
    {

        //get all categories
        $categories = $this->entityManager->getRepository(Category::class)->findAll();

        $questionnairesTotalRaw = [];
        /** @var Category $category * */
        foreach ($categories as $category) {
            $questionnairesTotalRaw[] = count($category->getTeacherQuestionnairesSchoolAuthority());
        }
        return array_sum($questionnairesTotalRaw);

    }

    public function getHeaderChartValue($school, $category)
    {
        $toReturn = 0;
        if (!$school || !$category) {
            return $toReturn;
        }

        $schoolId = $school->getId();
        $categoryId = $category->getId();
        $resultRepo = $this->entityManager->getRepository(ResultAnswer::class);
        $results = $resultRepo->getMeanByCategoriesAllUsers($schoolId, $categoryId);

        if (empty($results)) {
            return $toReturn;
        }
        //sum the values to get the category mean
        $numberOfResults = 0;
        $meanArray = [];
        //loop over questionnaires
        foreach ($results as $result) {
            //only if 2 teachers
            if ($result['users'] > 1) {
                $meanArray[] = $result['value'];
                $numberOfResults++;
            }

        }
        if ($numberOfResults > 0) {
            $toReturn = array_sum($meanArray) / $numberOfResults;
        }
        return $toReturn;
    }

    public function getNumberOfQuestionnairesDone($userId): int
    {
        //get the questionnaires filled out by the user
        //BUT NOT SORTED BY CATEGORY
        $questionnairesDone = $this->entityManager->getRepository(Result::class)->getQuestionnairesDone($userId);
        return count($questionnairesDone);
    }

    public function getMeanForAllCategories($userId): float
    {
        $results = $this->entityManager->getRepository(ResultAnswer::class)->getMeanForAllCategories($userId);
        $toReturn = 0;
        if (!empty($results)) {
            $meanArray = [];
            $numberOfQuestionnaires = count($results);
            foreach ($results as $result) {
                $meanArray[] = $result['value'];
            }

            $toReturn = array_sum($meanArray) / $numberOfQuestionnaires;

        }

        return $toReturn;
    }
}
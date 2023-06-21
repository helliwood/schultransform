<?php

namespace Trollfjord\Service\Dashboard;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Utils;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\PublicUserBundle\Repository\UserRepository;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\ResultAnswer;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionnaireRepository;
use Trollfjord\Entity\School;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Utils\DownloadContainer;
use Twig\Environment;

class SchoolService extends BaseService
{


    /************************************************ ACTIONS DATA **************************************/

    /** STAR SITE **/
    public function getStarSiteData($user): array
    {
        $schoolId = $user->getSchool()->getId();
        //  list($strongPoints, $weakPoints) = $this->getCategoriesFlagged($schoolId);
        list($strongPoints, $weakPoints, $numberOfQuestionnairesDone, $oneMoreToCount) = $this->getQuestionnaires($schoolId);
        $categoriesToConsider = array_merge($weakPoints, $strongPoints);
        $questionairesTotal = $this->getAllQuestionnairesAllCategories();
        $generalMean = $this->getMeanForAllCategoriesAllUsers($schoolId);
        $teacherNr = $this->getNumberOfTeachersOfSchool($schoolId);
        $data = [
            'cssClassWrapper' => 'my-star-site',
            'dashboardUser' => 'dashboard-user',
            'user' => $user,
            'anz' => $numberOfQuestionnairesDone,
            'open' => $questionairesTotal - $numberOfQuestionnairesDone,
            'ges' => $questionairesTotal,
            'teacherNr' => $teacherNr,
            'activeTeacher' => $this->getTeacher($schoolId),
            'headerChartValue' => $generalMean,
            'strongPoints' => $strongPoints,
            'weakPoints' => $weakPoints,
            'modalAllCategories' => $this->getModalAllCategories($categoriesToConsider),
            'allCats' => $categoriesToConsider,
            'oneMoreToCount' => $oneMoreToCount,
            'transformationIndex' => $generalMean,
        ];
        return $data;

    }


    /** POTENTIAL *
     * @throws Exception
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function potential($user, $category): array
    {
        $userId = $user->getId();
        $school = $user->getSchool() ?: null;

        $transformationIndex = 0;
        if ($school) {
            $schoolId = $school->getId();
            $transformationIndex = $this->getMeanForAllCategoriesAllUsers($schoolId);
        } else {
            return [];
        }

        $categoryId = $category->getId();

        $chartQuestionnaires = $this->getChartQuestionnaires($school, $categoryId);
        $questionairs = $this->getAllQuestionairsFormated($userId);
        list($numberQuestionnairesCompleted, $numberQuestionnairesNotCompleted) = $this->questionnairesCompletedAndNot($schoolId, $category);
        $teachersActiveByCategory = $this->teachersActiveByCategory($category, $school);

        $headerChartValue = $this->getHeaderChartValue($school, $category);

        /** @var Category $category * */
        $categoryQuestionnaires = $category->getTeacherQuestionnaires();
        $questionnairesDoneInCategory = $this->getQuestionnairesByCategory($school, $categoryId);


        //get recommendations for each questionnaire in category
        $recommendations = [];
        $recommendationsAdvanced = [];
        foreach ($questionnairesDoneInCategory as $item) {
            $questionnaireId = $item['questionnaireId'];
            $recommendationsRaw = $this->getRecommendationsByQuestionnaire($schoolId, $questionnaireId);
            $recommendationsAdvancedRaw = $this->getRecommendationsAdvancedByQuestionnaire($schoolId, $questionnaireId);
            if (!empty($recommendationsAdvancedRaw)) {
                $recommendationsAdvanced[] = $recommendationsAdvancedRaw;
            }
            if (!empty($recommendationsRaw)) {
                $recommendations[] = $recommendationsRaw;
            }
        }


        $teacherNr = $this->getNumberOfTeachersOfSchool($schoolId);
        $downloads = $this->getDownloads($questionnairesDoneInCategory);
        $dataToReturn = [
            'cssClassWrapper' => 'category-overview',
            'dashboardUser' => 'dashboard-user category',
            'user' => $user,
            'open' => $numberQuestionnairesNotCompleted,
            'questionairsInCat' => count($categoryQuestionnaires),
            'category' => $category,
            'headerChartValue' => $headerChartValue,
            'teachersActiveByCategory' => count($teachersActiveByCategory),
            'results' => $chartQuestionnaires,
            'numberQuestionnairesDone' => $numberQuestionnairesCompleted,
            'downloads' => $downloads,
            'questionairs' => $questionairs,
            'numberQuestionnairesNotCompleted' => $numberQuestionnairesNotCompleted,
            'recommendations' => $recommendations,
            'recommendationsAdvanced' => $recommendationsAdvanced,
            'transformationIndex' => $transformationIndex,
            'teacherNr' => $teacherNr,
        ];

        return $dataToReturn;

    }

    /**  potential-questionnaire *
     * @throws Exception
     * @throws \Exception
     */
    public function potentialQuestionnaire($user, $questionnaire): array
    {
        //retrieve the data for one questionnaire to show the potential data
        $userId = $user->getId();
        $school = $user->getSchool() ?: null;

        $transformationIndex = 0;
        if ($school) {
            $schoolId = $school->getId();
            $transformationIndex = $this->getMeanForAllCategoriesAllUsers($schoolId);
        } else {
            return [];
        }


        /** @var Category $category * */
        $category = $questionnaire->getCategory();
        $potQuest = 0;
        foreach ($category->getQuestionnaires() as $que) {
            if ($que->getType() == "school") {
                $potQuest++;
            }
        }

        $categoryId = $category->getId();

        $questionnaireId = $questionnaire->getId();

        $questionnaireData = $this->getQuestionnairePotential($schoolId, $questionnaireId);

        $questionnairesInCategory = $this->getQuestionnairesByCategory($school, $categoryId);

        $questionairs = $this->getAllQuestionairsFormated($userId,);
        list($numberQuestionnairesCompleted, $numberQuestionnairesNotCompleted) = $this->questionnairesCompletedAndNot($schoolId, $category);
        $teachersActiveByCategory = $this->teachersActiveByCategory($category, $school);
        //get the recommendations for the questions under or equal 4
        $recommendations = [];
        $recommendationsRaw = $this->getRecommendationsByQuestionnaire($schoolId, $questionnaireId);
        $recommendationsAdvancedRaw = $this->getRecommendationsAdvancedByQuestionnaire($schoolId, $questionnaireId);
        $recommendationsAdvanced = [];
        if (!empty($recommendationsAdvancedRaw)) {
            $recommendationsAdvanced[] = $recommendationsAdvancedRaw;
        }
        if (!empty($recommendationsRaw)) {
            $recommendations[] = $recommendationsRaw;
        }

        //get the header chart value for all questionnaires done(last result record)
        // by category and by school
        $headerChartValue = $this->getHeaderChartValue($school, $category);
        $teachersActiveByQuestionnaire = $questionnaireData[0]['users'];

        $numberOfQuestionnaireInCategory = $category->getTeacherQuestionnaires();
        $downloads = $this->getDownloads($questionnairesInCategory, $questionnaire);
        $teacherNr = $this->getNumberOfTeachersOfSchool($schoolId);
        $dataToReturn = [
            'cssClassWrapper' => 'potential-questionnaire',
            'dashboardUser' => 'dashboard-user category',
            'user' => $user,
            'category' => $category,
            'headerChartValue' => $headerChartValue,
            'questionairsInCat' => count($numberOfQuestionnaireInCategory),
            'teachersActiveByCategory' => count($teachersActiveByCategory),
            'results' => $questionnairesInCategory,
            'questionairs' => $questionairs,
            'numberQuestionnairesDone' => $numberQuestionnairesCompleted,
            'numberQuestionnairesNotCompleted' => $numberQuestionnairesNotCompleted,
            'open' => $numberQuestionnairesNotCompleted,
            'recommendations' => $recommendations,
            'recommendationsAdvanced' => $recommendationsAdvanced,
            'questionnaire' => $questionnaire,
            'downloads' => $downloads,
            'teachersActiveByQuestionnaire' => $teachersActiveByQuestionnaire,
            'transformationIndex' => $transformationIndex,
            'teacherNr' => $teacherNr,

        ];
        return $dataToReturn;


    }


    /************************** HELPER FUNCTIONS ***********************************/


    //get partner material by id

    public function getMaterial($id)
    {
        $s = md5('tr-2023-wEtzodfk8_eridolskI8P');
        $c = md5('tr-st-u-auth-f');
        $url = 'https://partner.schultransform.org/MediaShare/get-material-for-school';

        //make the call to retrieve the material
        $response = $this->httpClient->request(
            'GET', $url,
            [
                'auth_basic' => [$c, $s],
                'query' => [
                    'materialId' => $id
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {

            $headers = $response->getHeaders();

            //set true to prepare for checking
            $contentType = null;
            $headerContentDisposition = null;

            if (isset($headers['content-type']) && (!empty($headers['content-type']))) {
                $contentType = $headers['content-type'][0];
            }

            if (isset($headers['content-disposition']) && (!empty($headers['content-disposition']))) {
                $headerContentDisposition = $headers['content-disposition'][0];
            }

            if (!isset($contentType) || !isset($headerContentDisposition)) {
                return false;
            }

            $content = $response->getContent();
            $response = new Response();
            $response->headers->set("Content-Type", $contentType);
            $response->headers->set("Content-Length", strlen($content));
            $response->headers->set("Content-Disposition", $headerContentDisposition);
            $response->setContent($content);
            return $response;

        }

        return false;


    }

    //get mean for all users for all categories

    public function getQuestionnairesFilledOut($schoolId)
    {
        $questionnaireRepo = $this->entityManager->getRepository(Questionnaire::class);


        return $questionnaireRepo->getQuestionnairesFilledOut($schoolId);
    }

    public function getResultsByCategoryForSchool($schoolId)
    {
        $resultRepo = $this->entityManager->getRepository(Result::class);
        return $resultRepo->getQuestionnairesFilledOut($schoolId);
    }

    public function getResultsByCategoryForSchool_Old($schoolId)
    {
        $categoryRepo = $this->entityManager->getRepository(Category::class);
        return $categoryRepo->getResultsByCategoryForSchool($schoolId);
    }
//
//    public function getQuestionnairesFilledOut($schoolId): array
//    {
//        $categoriesWithQuestionnairesFilledOut = $this->getResultsByCategoryForSchool($schoolId);
//    }

    public function getQuestionnaires($schoolId): array
    {
        $resultRepo = $this->entityManager->getRepository(Result::class);
        $questionnaires = $resultRepo->getQuestionnairesFilledOut($schoolId);
        //Check if each questionnaire has more than two teachers and
        //separate questionnaires with the mean bigger equal than 4 and less than 4

        $countQuestionnaires = 0; //to track the number of questionnaires with two teachers
        $strongPoints = [];
        $weakPoints = [];
        $oneMoreToCount = [];
        if (!empty($questionnaires)) {
            foreach ($questionnaires as $questionnaire) {
                if ($questionnaire['users'] > 1) {
                    $countQuestionnaires++;
                    //SEPARATE QUESTIONNAIRES BASE ON THE MEAN
                    if ($questionnaire['mean'] > 4) {
                        $strongPoints[] = $questionnaire;
                    }
                    if ($questionnaire['mean'] <= 4) {
                        $weakPoints[] = $questionnaire;
                    }
                }
                if ($questionnaire['users'] == 1) {
                    $oneMoreToCount[] = $questionnaire;
                }

            }

        }

        return [$strongPoints, $weakPoints, $countQuestionnaires, $oneMoreToCount];
    }

    private function separateQuestionnairesByMean($questionnaires)
    {

    }


    public function getCategoriesFlagged($schoolId): array
    {


        //get all categories to render
        //$categories = $this->entityManager->getRepository(Category::class)->findAll();

        //get the categories from the results by user
        //select all results and hold the categories
        $categories = $this->getResultsByCategoryForSchool($schoolId);
        //flag the categories which has a questionnaire filled out

        $catWith = [];
        $catWithout = [];
        foreach ($categories as $keyCategory => $category) {

            if ($category['rating'] >= 3) {
                $category['schoolHasQuestionnaires'] = false;
                $catWithout[] = $category;
            } else {
                $category['schoolHasQuestionnaires'] = true;
                $catWith[] = $category;
            }
        }
        return [$catWith, $catWithout];
    }

    private function arrayCategoryFlat($array)
    {

        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $value) {
            $result [] = $value['catId'];
        }


        return $result;
    }

    private function arrayCategoriesIds($categories)
    {
        if (!is_array($categories)) {
            return false;
        }
        $result = [];
        $categoriesKeyAsIDs = [];
        foreach ($categories as $category) {
            $result [] = $category['categoryId'];
            $categoriesKeyAsIDs[$category['categoryId']] = $category;
        }


        return [$result, $categoriesKeyAsIDs];
    }

    public function getAllQuestionairsFormated($userId = null, $category = null)
    {
        $result = [];
        $latestResultsOfUser = [];
        $catId = 0;

        if ($category) {
            $where = array('type' => 'school', 'category' => $category);
        } else {
            $where = array('type' => 'school');
        }

        $questionairs = $this->entityManager->getRepository(Questionnaire::class)->findBy($where);

        if ($userId) {
            $latestResultsOfUser = $this->arrayResultsFlat($this->entityManager->getRepository(Result::class)->getFilledOutQuestionaires($userId));
        }


        foreach ($questionairs as $questionair) {
            $site = $questionair->getSite();
            $questId = $questionair->getId();
            $slug = "";
            $fillOutDate = null;
            if ($site) {
                $slug = $site->getUrl();
            }

            $catId = $questionair->getCategory()->getId();
            if (!isset($result[$catId])) {
                $result[$catId] = [];
            }

            if (isset($latestResultsOfUser[$catId][$questId])) {
                $fillOutDate = $latestResultsOfUser[$catId][$questId]['creationDate'];
            }

            $result[$catId][$questionair->getId()] = [
                'name' => $questionair->getName(),
                'icon' => $questionair->getIcon(),
                'slug' => $slug,
                'doneat' => $fillOutDate
            ];
        }

        return $result;
    }

    private function arrayResultsFlat($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $value) {

            $value['creationDate'] = $value['creationDate']->format('d.m.Y H:i:s');
            $catId = $value['categoryID'];
            unset($value['categoryID']);
            if (!isset($result[$catId])) {
                $result[$catId] = [];
            }
            $result[$catId][$value['questionairID']] = $value;
        }
        return $result;
    }


    public function getQuestionnairePotential($schoolId, $questionnaireId): array
    {
        $result = $this->entityManager->getRepository(ResultAnswer::class)->getChartDataForContentByQuestionnaire($schoolId, $questionnaireId);

        $toReturn = [];


        if (!empty($result)) {
            //return the first element in array
            $toReturn = $result[0];
        }

        return $result;
    }

    public function getChartDataForContentByCategory($schoolId, $categoryId): array
    {
        $results = $this->entityManager->getRepository(ResultAnswer::class)->getChartDataBySchoolByCategory($schoolId, $categoryId);
        $categoryMean = 0;
        //add the mean by category(sum of all values divided by total)
        $valuesPerCategory = [];
        $numberOfAnswers = [];
        $numberOfQuestionnaires = count($results);
        if (!empty($results)) {
            foreach ($results as $result) {
                $valuesPerCategory[] = $result['sumOfValues'];
                $numberOfAnswers[] = $result['answers'];
            }
            $categoryMean = array_sum($valuesPerCategory) / array_sum($numberOfAnswers);
        }
        return [$results, $categoryMean];
    }

    private function questionnairesCompletedAndNot_OLD($user, $category): int
    {
        $toReturn = 0;
        $categoryId = $category->getId();
        $userId = $user->getId();
        $questionnairesDone = $this->entityManager->getRepository(Result::class)->getQuestionnairesDonePerCategoryAndUser($userId, $categoryId);
        $questionnairesDone = $this->sortedCategoriesWithQuestionnaires($questionnairesDone);
        $questionnairesDone = count($questionnairesDone);

        $questionnairesInCategory = $category->getTeacherQuestionnaires();
        $questionnairesInCategory = count($questionnairesInCategory);

        if ($questionnairesInCategory > $questionnairesDone) {

            $toReturn = $questionnairesInCategory - $questionnairesDone;
        }


        return $toReturn;

    }

    /*** TODO: Simplify  **/
    private function getDataToOverview($categoryData, $userId, $categoryId): array
    {

        $dates = []; // includes all the dates + amount of actions per category that where done
        $userDates = []; // same as $dates but only for the questionaires made by the user
        $toReturn = [];
        $arrayDownloads = [];
        foreach ($categoryData as $resultKey => $resultCategory) {
            $catId = $resultCategory['categoryId'];
            $questName = $resultCategory['questionnaireName'];
            $resultId = $resultCategory['resultId'];
            $resultsIn[$catId][] = true;
            $resultUrl = '';


            //GET all existing Results to show activity chart
            list($earliestDate, $catId, $formatedDate, $dates, $userDates) = $this->activityresults([$userId], $dates, $userDates, $userId);
            //check if there is a page associated with the current questionnaire and put it to the downloads
            if ($resultCategory['Site'] != '') {
                if ($site = $this->entityManager->getRepository(Site::class)->find($resultCategory['Site'])) {
                    $resultUrl = $site->getUrl();
                }
            }
            $resultCategory['urlQuestionnaire'] = $resultUrl;


            //if it's a result from the current user, put result pdf into downloads
            $downloadUrl = '/print-result/' . $resultId;
            $download = new DownloadContainer('Ihre Momentaufnahme zum Thema:  ' . $questName . '', 'ausgefüllt amasd ' . $formatedDate, $downloadUrl, 'Herunterladen (PDF)', DownloadContainer::TYPE_DOWNLOAD);
            $arrayDownloads[] = $download->getArray();

            $toReturn[] = $resultCategory;

        }

        return [$toReturn, $arrayDownloads];

    }

    private function sortedCategoriesWithQuestionnaires($results): array
    {
        //make an array of categories ids
        $categoriesIdsArray = [];
        $toReturn = [];
        foreach ($results as $result) {
            $categoriesIdsArray[] = $result['id'];
        }
        $categoriesIdsArray = array_unique($categoriesIdsArray);


        foreach ($results as $result) {
            if (in_array($result['id'], $categoriesIdsArray)) {
                $toReturn[$result['id']]['id'] = $result['id'];
                $toReturn[$result['id']][] = $result['name'];
            }
        }
        return $toReturn;

    }

    private function getModalAllCategories(array $categoriesToConsider): array
    {
        $allCategories = $this->entityManager->getRepository(Category::class)->findAll();
        list($categoriesToConsiderIds, $categoriesKeyAsIDs) = $this->arrayCategoriesIds($categoriesToConsider);

        $toReturn = [];

        //loop to flag the category that has questionnaires(2 teachers min.)
        foreach ($allCategories as $key => $category) {
            $toReturn[$key] = $category->toArray();


            if (in_array($category->getId(), $categoriesToConsiderIds)) {
                $creatDate = $categoriesKeyAsIDs[$category->getId()]['creationDate'];

                $creatDate = date_create($creatDate);
                $creatDate = date_format($creatDate, "d.m.Y H:i:s");
                $toReturn[$key]['questionnairesFilledOut'] = true;
                $toReturn[$key]['creationDate'] = (string)$creatDate;
            }

        }
        return $toReturn;

    }
}

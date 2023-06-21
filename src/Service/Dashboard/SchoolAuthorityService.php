<?php

namespace Trollfjord\Service\Dashboard;

use DateTime;
use Doctrine\DBAL\Exception;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\ResultAnswer;
use Trollfjord\Entity\School;
use Trollfjord\Utils\DownloadContainer;

class SchoolAuthorityService extends BaseService
{


    /************************************************ ACTIONS DATA **************************************/

    /** STAR SITE **/
    public function getStarSiteData($user): array
    {
        $userId = $user->getId();
        $schoolAuthority = $user->getSchoolAuthority() ?: null;
        //header value

        $headerChartValue = $this->getMeanForAllCategories($userId);
        //number of questionnaires total
        $nrQuestionaires = $this->getAllQuestionnairesAllCategoriesSchoolAuthority();

        //check if two teachers have filled out the same questionnaire
        //loop the schools
        $allowOpenPotential = null;
        foreach ($schoolAuthority->getSchools() as $schoolItem) {
            //$chartQuestionnaires = $this->getChartQuestionnaires($schoolItem, $categoryId);
            $allowOpenPotential[$schoolItem->getId()] = false;
//            if (!empty($chartQuestionnaires)) {
            $allowOpenPotential[$schoolItem->getId()] = true;
//            }
        }

        $questionnairesInCategory = $nrQuestionaires;

        $data = [
            'cssClassWrapper' => 'my-star-site',
            'dashboardUser' => 'dashboard-user', //class used for breadcrumb
            'nrQuestionaires' => $nrQuestionaires,
            'numberQuestionnairesDone' => $this->getNumberOfQuestionnairesDone($userId),
            'user' => $user,
            'headerChartValue' => $headerChartValue,
            'categories' => $this->getCategoriesFlagged($userId),
            'questionairs' => $this->getAllQuestionairsFormated($userId),
            'allowOpenPotential' => $allowOpenPotential,
            'transformationIndex' => $headerChartValue,
            'questionairsInCat' => $questionnairesInCategory,
            'allQuestionnaires' => $nrQuestionaires,
            'allQuestionnairesFilledOut' => $this->getNumberOfQuestionnairesDone($userId),

        ];
        return $data;
    }

    /** CATEGORY OVERVIEW *
     * @throws Exception
     */
    public function CategoryOverviewData($user, $category): array
    {
        /** TODO: Simplify **/
        $userId = $user->getId();
        $schoolAuthority = $user->getSchoolAuthority() ?: null;
        $categoryId = $category->getId();
        $dataToReturn = [];
        //get the result for the charts values by user and by category.
        //Questionnaires made by the user in this category and the mean for each of them and for all of them.


        list($categoryData, $categoryMean) = $this->getChartDataForContentByCategory($userId, $categoryId);
        $questionnairesDone = $this->entityManager->getRepository(Result::class)->getQuestionnairesDonePerCategoryAndUser($userId, $categoryId);

        $transformationIndex = $this->getMeanForAllCategories($userId);

        //return false in case that the user change the category
        //and the category has no questionnaires filled out
        $questionairs = $this->getAllQuestionairsFormated($userId,);
        $numberQuestionnairesNotCompleted = $this->countQuestionnairesNotCompleted($user, $category);

        //number of questionnaires total
        $nrQuestionaires = $this->getAllQuestionnairesAllCategoriesSchoolAuthority();

        //get recommendations for each questionnaire in category
        $recommendations = [];
        $recommendationsAdvanced = [];
        $questionnairesDone = $this->entityManager->getRepository(Result::class)->getQuestionnairesDonePerCategoryAndUser($userId, $categoryId);

        foreach ($questionnairesDone as $item) {
            $recommendationsRaw = $this->getRecommendationsByQuestionnaireSchoolAuthority($userId, $item['id']);
            $recommendationsAdvancedRaw = $this->getRecommendationsAdvancedByQuestionnaireSchoolAuthority($userId, $item['id']);
            if (! empty($recommendationsAdvancedRaw)) {
                $recommendationsAdvanced[] = $recommendationsAdvancedRaw;
            }
            if (! empty($recommendationsRaw)) {
                $recommendations[] = $recommendationsRaw;
            }

        }


        $questionnairesInCategory = count($category->getSchoolAuthorityQuestionnaires());
        list($results, $downloads) = $this->getDataToOverview($categoryData, $userId, $categoryId);

        //open is the number of questionnaires not done
        if (! empty($results)) {
            $open = $questionnairesInCategory - count($results);
        } else {
            $open = $questionnairesInCategory;
        }

        $dataToReturn = [
            'open' => $open,
            'questionairsInCat' => $questionnairesInCategory,
            'cssClassWrapper' => 'category-overview',
            'dashboardUser' => 'dashboard-user category',
            'user' => $user,
            'category' => $category,
            'numberQuestionnairesInCategory' => count($category->getSchoolAuthorityQuestionnaires()),
            'results' => $results,
            'numberQuestionnairesDone' => count($results),
            'headerChartValue' => $categoryMean,
            'downloads' => $downloads,
            'questionairs' => $questionairs,
            'recommendations' => $recommendations,
            'recommendationsAdvanced' => $recommendationsAdvanced,
            'numberQuestionnairesNotCompleted' => $numberQuestionnairesNotCompleted,
            'transformationIndex' => $transformationIndex,
            'allQuestionnaires' => $nrQuestionaires,
            'allQuestionnairesFilledOut' => $this->getNumberOfQuestionnairesDone($userId),
        ];
        return $dataToReturn;

    }


    /** POTENTIAL *
     * @throws Exception
     * @throws \Exception
     */
    public function potential_OLD($school, $category): array
    {
        $schoolId = $school->getId();
        $categoryId = $category->getId();
        $user = $school->getMainUser();
        $chartQuestionnaires = $this->getChartQuestionnaires($school, $categoryId);
//        $questionairs = $this->getAllQuestionairsFormated($userId);
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
            $recommendations[] = $this->getRecommendationsByQuestionnaire($schoolId, $item['questionnaireId']);
            $recommendationsAdvancedRaw = $this->getRecommendationsAdvancedByQuestionnaire($schoolId, $item['questionnaireId']);
            if (! empty($recommendationsAdvancedRaw)) {
                $recommendationsAdvanced[] = $recommendationsAdvancedRaw;
            }
        }

        $downloads = $this->getDownloadsSchoolAuthority($questionnairesDoneInCategory, $schoolId);
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
            //            'questionairs' => $questionairs,
            'numberQuestionnairesNotCompleted' => $numberQuestionnairesNotCompleted,
            'recommendations' => $recommendations,
            'recommendationsAdvanced' => $recommendationsAdvanced,
        ];
        return $dataToReturn;

    }


    /** POTENTIAL *
     * @throws Exception
     * @throws \Exception
     */
    public function potential($school): array
    {
        $transformationIndex = 0;
        if ($school) {
            $schoolId = $school->getId();
            $transformationIndex = $this->getMeanForAllCategoriesAllUsers($schoolId);
        } else {
            return [];
        }


        //get the charts one per category
        $allCategories = $this->entityManager->getRepository(Category::class)->findAll();
        $categoriesCharts = [];
        foreach ($allCategories as $category) {
            $headerChartValue = $this->getHeaderChartValue($school, $category);
            $teachersActiveByCategory = $this->teachersActiveByCategory($category, $school);

            //collect all teachers codes in an array
            $lasActivityByCategory = new DateTime();
            if (! empty($teachersActiveByCategory)) {
                $arrayCodeTeachers = [];
                foreach ($teachersActiveByCategory as $teacher) {
                    $arrayCodeTeachers[] = $teacher['code'];
                }
                $lasActivityByCategory = $this->getTeachersLastActivityDateByCategory($category, $arrayCodeTeachers);
            }
            $categoriesCharts[] = [
                'name' => $category->getName(),
                'color' => $category->getColor(),
                'icon' => $category->getIcon(),
                'mean' => $headerChartValue,
                'nrTeachers' => count($teachersActiveByCategory),
                'lastActivity' => $lasActivityByCategory,
            ];
        }
        $user = $school->getMainUser();
        //chart value for all categories
        $generalMean = $this->getMeanForAllCategoriesAllUsers($schoolId);

        $dataToReturn = [
            'cssClassWrapper' => 'category-overview',
            'dashboardUser' => 'dashboard-user category',
            'user' => $user,
            'school' => $school,
            'teacherNr' => $this->getTeacher($schoolId),
            'headerChartValue' => $generalMean,
            'results' => $categoriesCharts,
            'transformationIndex' => $transformationIndex,

        ];
        return $dataToReturn;

    }

    public function getChartDataForContentByCategory($userId, $categoryId): array
    {
        $results = $this->entityManager->getRepository(ResultAnswer::class)->getChartDataForContentByCategory($userId, $categoryId);
        $categoryMean = 0;
        //add the mean by category(sum of all values divided by total)
        $valuesPerCategory = [];
        $numberOfAnswers = [];
        $numberOfQuestionnaires = count($results);
        if (! empty($results)) {
            foreach ($results as $result) {
                $valuesPerCategory[] = $result['sumOfValues'];
                $numberOfAnswers[] = $result['answers'];
            }
            $categoryMean = array_sum($valuesPerCategory) / array_sum($numberOfAnswers);
        }

        return [$results, $categoryMean];
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
            $questionnaireId = $resultCategory['questionnaireId'];


            //GET all existing Results to show activity chart
            $formatedDate = $this->getResultLastDate($userId, $questionnaireId);
            //check if there is a page associated with the current questionnaire and put it to the downloads
            if ($resultCategory['Site'] != '') {
                if ($site = $this->entityManager->getRepository(Site::class)->find($resultCategory['Site'])) {
                    $resultUrl = $site->getUrl();
                }
            }
            $resultCategory['urlQuestionnaire'] = $resultUrl;


            //if it's a result from the current user, put result pdf into downloads
            $downloadUrl = '/print-result/' . $resultId;
            $download = new DownloadContainer('Ihre Momentaufnahme zum Thema:  ' . $questName . '', 'ausgefüllt am ' . $formatedDate, $downloadUrl, 'Herunterladen (PDF)', DownloadContainer::TYPE_DOWNLOAD);
            $arrayDownloads[] = $download->getArray();

            $toReturn[] = $resultCategory;
        }
        return [$toReturn, $arrayDownloads];

    }

    private function countQuestionnairesNotCompleted($user, $category): int
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

    private function arrayCategoryFlat($array)
    {
        if (! is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $value) {
            $result [] = $value['id'];
        }
        return $result;
    }

    public function getCategoriesFlagged($userId): array
    {

        //get all categories to render
        $categories = $this->entityManager->getRepository(Category::class)->findAll();

        //get the categories from the results by user
        //select all results and hold the categories
        $categoriesWithQuestionnairesFilledOut = $this->entityManager->getRepository(Result::class)->getCategoriesFromQuestionnairesFilledOut($userId);


        //sorted the categories with their questionnaires
        $sortedCategoriesWithQuestionnaires = $this->sortedCategoriesWithQuestionnaires($categoriesWithQuestionnairesFilledOut);

        //flag the categories which has a questionnaire filled out
        $flattedCatArray = [];
        if (! empty($sortedCategoriesWithQuestionnaires)) {
            $flattedCatArray = $this->arrayCategoryFlat($sortedCategoriesWithQuestionnaires);
        }
//        dd($categoriesWithQuestionnairesFilledOut, $flattedCatArray);
        //flag the categories when there are no questionnaires filled out
        /** @var $category Category * */
        foreach ($categories as $category) {

            //set the category questionnaire
            $categoryQuestionnaire = null;
            $questionnaires = $category->getSchoolAuthorityQuestionnaires();
            if (! empty($questionnaires)) {
                $categoryQuestionnaire = array_shift($questionnaires);
            }


            $categoryId = $category->getId();
            //get all questionnaires for each category
            $category->schoolAuthorityQuestionnaires = $category->getSchoolAuthorityQuestionnaires();
            if (in_array($categoryId, $flattedCatArray)) {
                $category->userHasQuestionnairesFilledOut = true;
                $category->noQuestionnairesFilledOutByUser = false;


                //delete the key 'id' from the array
                unset($sortedCategoriesWithQuestionnaires[$categoryId]['id']);
                $category->questionnairesFilledOut = $sortedCategoriesWithQuestionnaires[$categoryId];
//                $sortedCategoriesWithQuestionnaires[$categoryId];
            } else {
                $category->setColor('#909090');
                $category->noQuestionnairesFilledOutByUser = true;
                $category->userHasQuestionnairesFilledOut = false;

            }
            $category->standAloneQuestionnaire = $categoryQuestionnaire;
        }

        return $categories;
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

    public function getAllQuestionairsFormated($userId = null, $category = null)
    {
        $result = [];
        $latestResultsOfUser = [];
        $catId = 0;

        if ($category) {
            $where = array('type' => 'school_board', 'category' => $category);
        } else {
            $where = array('type' => 'school_board');
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
            if (! isset($result[$catId])) {
                $result[$catId] = [];
            }

            if (isset($latestResultsOfUser[$catId][$questId])) {
                $fillOutDate = $latestResultsOfUser[$catId][$questId]['creationDate'];
            }

            $result[$catId][$questionair->getId()] = [
                'id' => $questionair->getId(),
                'name' => $questionair->getName(),
                'icon' => $questionair->getIcon(),
                'slug' => $slug,
                'doneat' => $fillOutDate
            ];
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getAllQuestionnairesFromSchool(School $school): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $sql = <<<'SQL'
            SELECT q.id, q.name, c.id as categoryId, c.name as categoryName, s.id as schoolId, r.creation_date as creationDate
            FROM questionnairebundle_category c 
            LEFT JOIN school s on s.school_authority_id  = :schoolAuthorityId
            LEFT JOIN publicuserbundle_user u on u.code IS NOT NULL AND s.id = u.school_id
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            WHERE q.type = 'school' AND s.id = :schoolId
            GROUP BY q.id;
SQL;
        $data = $this->entityManager->getConnection()->fetchAllAssociative($sql, ['schoolAuthorityId' => $user->getSchoolAuthority()->getId(), 'schoolId' => $school->getId()]);

        return $data;
    }


    private function arrayResultsFlat($array)
    {
        if (! is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $value) {

            $value['creationDate'] = $value['creationDate']->format('d.m.Y H:i:s');
            $catId = $value['categoryID'];
            unset($value['categoryID']);
            if (! isset($result[$catId])) {
                $result[$catId] = [];
            }
            $result[$catId][$value['questionairID']] = $value;
        }
        return $result;
    }
}
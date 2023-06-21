<?php

namespace Trollfjord\Service\Dashboard;

use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\PublicUserBundle\Repository\UserRepository;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\ResultAnswer;
use Trollfjord\Utils\DownloadContainer;
use Twig\Environment;
use function Symfony\Component\String\s;
use function Symfony\Component\String\u;

class TeacherService extends BaseService
{


    /************************************************ ACTIONS DATA **************************************/

    /** STAR SITE **/
    public function getStarSiteData($user): array
    {
        $userId = $user->getId();
        $nrQuestionaires = 0;
        $questionaires = $this->getAllQuestionairsFormated($userId);


        //in case that the user d no have a school
        $teacherNr = 0;
        $transformationIndex = 0;
        if ($user->getSchool()) {
            $schoolId = $user->getSchool()->getId();
            $teacherNr = $this->getNumberOfTeachersOfSchool($schoolId);
            $transformationIndex = $this->getMeanForAllCategoriesAllUsers($schoolId);
        }


        foreach ($questionaires as $questionaire) {
            $nrQuestionaires += count($questionaire);
        }


        return [
            'cssClassWrapper' => 'my-star-site',
            'dashboardUser' => 'dashboard-user',
            'user' => $user,
            'categories' => $this->getCategoriesFlagged($userId),
            'questionairs' => $questionaires,
            'nrQuestionaires' => $nrQuestionaires,
            'numberQuestionnairesDone' => $this->getNumberOfQuestionnairesDone($userId),
            'headerChartValue' => (int)$this->getMeanForAllCategories($userId),
            'teacherNr' => $teacherNr,
            'transformationIndex' => $transformationIndex,
        ];


    }

    /** CATEGORY OVERVIEW *
     * @throws \Exception
     */
    public function CategoryOverviewData($user, $category): array
    {

        $userId = $user->getId();
        $school = $user->getSchool() ?: null;
        $transformationIndex = 0;
        $teacherNr = 0;
        if ($school) {
            $schoolId = $school->getId();
            $transformationIndex = $this->getMeanForAllCategoriesAllUsers($schoolId);
            $teacherNr = $this->getNumberOfTeachersOfSchool($schoolId);
        }

        $categoryId = $category->getId();
        $dataToReturn = [];
        //get the result for the charts values by user and by category.
        //Questionnaires made by the user in this category and the mean for each of them and for all of them.
        list($categoryData, $categoryMean) = $this->getChartDataForContentByCategory($userId, $categoryId);

        //return false in case that the user change the category
        //and the category has no questionnaires filled out
        $questionairs = $this->getAllQuestionairsFormated($userId,);

        $numberQuestionnairesNotCompleted = $this->countQuestionnairesNotCompleted($user, $category);
        $teachersActiveByCategory = $this->teachersActiveByCategory($category, $school);

        //check if two teachers have filled out the same questionnaire
        $chartQuestionnaires = $this->getChartQuestionnaires($school, $categoryId);
        $allowOpenPotential = false;
        if (!empty($chartQuestionnaires)) {
            $allowOpenPotential = true;
        }

        $results = $this->getDataToOverview($categoryData);

        $downloads = $this->getDownloadsForTeachers($userId, $categoryId);

        if (empty($results)) {
            return [];
        }

        $dataToReturn = [
            'cssClassWrapper' => 'category-overview',
            'dashboardUser' => 'dashboard-user category',
            'user' => $user,
            'allowOpenPotential' => $allowOpenPotential,
            'category' => $category,
            'numberQuestionnairesInCategory' => count($category->getTeacherQuestionnaires()),
            'results' => $results,
            'numberQuestionnairesDone' => count($results),
            'headerChartValue' => $categoryMean,
            'downloads' => $downloads,
            'questionairs' => $questionairs,
            'numberQuestionnairesNotCompleted' => $numberQuestionnairesNotCompleted,
            'teachersActiveByCategory' => count($teachersActiveByCategory),
            'transformationIndex' => $transformationIndex,
            'teacherNr' => $teacherNr,
        ];


        return $dataToReturn;

    }


    /** POTENTIAL *
     * @throws Exception
     */
    public function potential($user, $category): array
    {
        $userId = $user->getId();
        $school = $user->getSchool() ?: null;
        $transformationIndex = 0;
        $teacherNr = 0;
        if ($school) {
            $schoolId = $school->getId();
            $transformationIndex = $this->getMeanForAllCategoriesAllUsers($schoolId);
            $teacherNr = $this->getNumberOfTeachersOfSchool($schoolId);
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
            $recommendationsRaw = $this->getRecommendationsByQuestionnaire($schoolId, $item['questionnaireId']);
            $recommendationsAdvancedRaw = $this->getRecommendationsAdvancedByQuestionnaire($schoolId, $item['questionnaireId']);
            if (!empty($recommendationsAdvancedRaw)) {
                $recommendationsAdvanced[] = $recommendationsAdvancedRaw;
            }
            if (!empty($recommendationsRaw)) {
                $recommendations[] = $recommendationsRaw;
            }

        }

        $downloads = $this->getDownloads($questionnairesDoneInCategory);

        $dataToReturn = [
            'cssClassWrapper' => 'potential',
            'dashboardUser' => 'dashboard-user potential',
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


    /** POTENTIAL **/
    public function potential_Old($user, $category): array
    {
        /******** TODO: CLEANING & REFACTORING !!!!!!!!!!!!!!************************************************************/
        $userId = $user->getId();
        $school = $user->getSchool() ?: null;
        $schoolId = $school->getId();
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
        foreach ($questionnairesDoneInCategory as $item) {
            $recommendations[] = $this->getRecommendationsByQuestionnaire($schoolId, $item['questionnaireId']);
        }

        $downloads = $this->getDownloads($questionnairesDoneInCategory);

        $dataToReturn = [
            'cssClassWrapper' => 'potential',
            'dashboardUser' => 'dashboard-user',
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
        ];


        return $dataToReturn;

    }


    /************************** HELPER FUNCTIONS ***********************************/

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
        if (!empty($sortedCategoriesWithQuestionnaires)) {
            $flattedCatArray = $this->arrayCategoryFlat($sortedCategoriesWithQuestionnaires);
        }

        //flag the categories when there are no questionnaires filled out
        /** @var $category Category * */
        foreach ($categories as $category) {
            $categoryId = $category->getId();
            //get all questionnaires for each category
            $category->teacherQuestionnaires = $category->getTeacherQuestionnaires();
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

        }


        return $categories;
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
                'id' => $questionair->getId(),
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

    private function arrayCategoryFlat($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $value) {
            $result [] = $value['id'];
        }
        return $result;
    }

    public function getCategories(): array
    {
        return $this->entityManager->getRepository(Category::class)->findAll();
    }

    public function getResultsByCategoryId($user, $category)
    {
        //get the results for the overview


    }

    public function getResults($user, $category): array
    {

        $toReturn = [];
        $category = $this->entityManager->getRepository(Category::class)->getResultsByCategoryAndUser($user->getId(), $category->getId());
        foreach ($category->getQuestionnaires() as $keyQues => $questionnaire) {
            $toReturn['questionnaire'][$keyQues]['name'] = $questionnaire->getName();
            foreach ($questionnaire->getResults() as $keyRes => $result) {
                // $toReturn['questionnaire'][$keyQues]['results'][$keyRes] = $result->toArray();
                foreach ($result->getAnswers() as $keyAnsw => $answer) {

                    $toReturn['questionnaire'][$keyQues]['results']['answers'][$keyAnsw] = [
                        'id' => $answer->getId(),
                        'value' => $answer->getValue(),
                        'question' => $answer->getQuestion()->getQuestion(),
                    ];
                }
            }
        }
        return $toReturn;
    }

    public function getChartDataForContentByCategory($userId, $categoryId): array
    {
        $results = $this->entityManager->getRepository(ResultAnswer::class)->getChartDataForContentByCategory($userId, $categoryId);


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

    /**
     * @throws \Exception
     */
    public function getDownloadsForTeachers($userId, $categoryId): array
    {
        $arrayDownloads = [];
        $categoryData = $this->entityManager->getRepository(ResultAnswer::class)->getDownloads($userId, $categoryId);

        foreach ($categoryData as $resultKey => $resultCategory) {
            $questName = $resultCategory['questionnaireName'];
            $resultId = $resultCategory['resultId'];
            $creationDate_ = $resultCategory['creationDate'];

            $downloadUrl = '/print-result/' . $resultId;
            $download = new DownloadContainer('Ihre Momentaufnahme zum Thema:  ' . $questName . '', 'ausgefüllt am ' . $creationDate_->format('d.m.Y H:m:s'), $downloadUrl, 'Herunterladen (PDF)', DownloadContainer::TYPE_DOWNLOAD);
            $arrayDownloads[] = $download->getArray();

        }

        return $arrayDownloads;
    }

    /***
     * @throws \Exception
     */
    private function getDataToOverview($categoryData): array
    {

        $toReturn = [];

        foreach ($categoryData as $resultCategory) {

            $resultUrl = '';

            //check if there is a page associated with the current questionnaire and put it to the downloads
            if ($resultCategory['Site'] != '') {
                if ($site = $this->entityManager->getRepository(Site::class)->find($resultCategory['Site'])) {
                    $resultUrl = $site->getUrl();
                }
            }
            $resultCategory['urlQuestionnaire'] = $resultUrl;

            $toReturn[] = $resultCategory;

        }

        return $toReturn;

    }


}

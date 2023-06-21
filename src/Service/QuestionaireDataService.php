<?php

namespace Trollfjord\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionGroup;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Twig\Environment;


/**
 * Class ChartService
 * @package Trollfjord\Service
 * @author Dirk Mertins
 */
class QuestionaireDataService
{

    private $token = null;
    private $userId = null;
    private $mainCats = array();
    private $categoryHelper = array(); //provides Categorie ids based on categoryname
    private $avgData = array();


    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;

        /** @var User $user * */
        $user = $security->getUser();
        if ($user) {
            $this->token = $user->getCode();
            $this->userId = $user->getId();
            $this->userRoles = $user->getRoles();
        }

    }


    public function createBoxPlotData($color = '#0d0ded', $name = "")
    {

        $ret = [];
        $ret['value'] = array(713.75, 807.5, 810, 870, 963.75);
        $ret['name'] = $name;
        $ret['itemStyle'] = array(
            'color' => $color,
            'borderColor' => '#000000'
        );
        return $ret;
    }


    public function initCatData()
    {
        $this->mainCats = $this->getCategories();


        $this->getCatData(null, null, null, null, Questionnaire::TYPE_SCHOOL);
        $this->getCatData(null, null, null, null, Questionnaire::TYPE_SCHOOL_BOARD);


        if ($this->userId) {
            $this->getCatData(null, null, $this->userId, true, Questionnaire::TYPE_SCHOOL);
        }
    }


    public function getCatData($mainCategoryId = null, $categoryId = null, $userId = null, $force = false, $type = null)
    {

        $group = 'Deutschland';
        $groupSpecifier = 'and questionnairebundle_question_group.position=1';
        $nameColumn = ' questionnairebundle_questionnaire.name ';
        $where = 'where 1=1 ';

        if ($type && $type == Questionnaire::TYPE_SCHOOL_BOARD) {
            $group = 'Schulträger';
            $nameColumn = ' questionnairebundle_question_group.name';
            $groupSpecifier = '';
        } else {
            $type = Questionnaire::TYPE_SCHOOL;
        }
        $where .= " and questionnairebundle_questionnaire.type='" . $type . "' ";

        if ($userId) {
            $group = "Ich";
            $where .= " and questionnairebundle_result.user_id='$userId' ";
            //TODO Eingeloggter Schulträger führt noch zu Problemen
        }


        if (!$force && isset($this->avgData[$group][$mainCategoryId][$categoryId])) {

            return $this->avgData[$group][$mainCategoryId][$categoryId];
        }


        $conn = $this->em->getConnection();
        $sql = 'Select
                round(avg(questionnairebundle_result_answer.value),2) as average,
                ' . $nameColumn . ' as theName,
                questionnairebundle_questionnaire.id as subCatId,
                questionnairebundle_questionnaire.category_id as mainCat
                from questionnairebundle_result
                    inner join  (select max(id) as foo from questionnairebundle_result group by user_id,questionnaire_id)  latest on latest.foo = questionnairebundle_result.id
                    inner join questionnairebundle_result_answer on questionnairebundle_result_answer.result_id = questionnairebundle_result.id
                    inner join questionnairebundle_question on questionnairebundle_question.id = questionnairebundle_result_answer.question_id
                    inner join questionnairebundle_question_group on questionnairebundle_question.question_group_id = questionnairebundle_question_group.id ' . $groupSpecifier . '
                    inner join questionnairebundle_questionnaire on questionnairebundle_result.questionnaire_id = questionnairebundle_questionnaire.id
                ' . $where . '
                group by theName';


        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();

        foreach ($ret as $retrow) {
            if ($group == "Schulträger") {
                //We have to get the subCat ID via the Categoryname since the regarding results for Schoolboards are in other categories then the school-ones
                if (!isset($this->categoryHelper[$retrow['theName']])) {
                    //skip if we have a "unknown" category
                    continue;
                }

                $subCatId = $this->categoryHelper[$retrow['theName']];

            } else {
                $subCatId = $retrow['subCatId'];
            }
            $this->avgData[$group][$retrow['mainCat']][$subCatId] = array(
                "group" => $group,
                "questionTag" => "PlanA",
                "answer" => (int)(($retrow['average'] / 7) * 100)
            );
        }

        if (isset($this->avgData[$group])) {
            return null;
        } else {
            return null;
        }
    }


    public function getSubCategories($catId)
    {
        $cr = $this->em->getRepository(Questionnaire::class);
        $foo = $cr->findBy(['category' => $catId, 'type' => Questionnaire::TYPE_SCHOOL],['name'=>'ASC']);
        return $foo;

    }


    public function getSourceData($mainCat, $subcat)
    {
        $ret = array();
        $ret[] = $this->getCatData($mainCat, $subcat, null, null, Questionnaire::TYPE_SCHOOL);
        $schoolBoardData = $this->getCatData($mainCat, $subcat, null, null, Questionnaire::TYPE_SCHOOL_BOARD);
        if($schoolBoardData){
            $ret[] = $schoolBoardData;
        }
        if ($this->userId) {
            $userResult = $this->getCatData($mainCat, $subcat, $this->userId, null, Questionnaire::TYPE_SCHOOL);

            if ($userResult) {
                $ret[] = $userResult;
            }
        }
        return $ret;
    }

    public function getCategories()
    {
        $cr = $this->em->getRepository(Category::class);
        $foo = $cr->findBy([]);
        $ret = array();

        foreach ($foo as $foos) {

            $tempArray = [];
            $tempArray['name'] = $foos->getName();
            $tempArray['color'] = $foos->getColor();
            $tempArray['catid'] = $foos->getId();
            $this->categoryHelper[$foos->getName()] = $foos->getId();
            $catBoxData[$foos->getId()] = $this->createBoxPlotData($foos->getColor(), $foos->getName());
            $catChilds = $this->getSubCategories($foos->getId());
            foreach ($catChilds as $catChild) {
                $this->categoryHelper[$catChild->getName()] = $catChild->getId();
                $tempArray['children'][] = array(
                    'name' => $catChild->getName(),
                    'catid' => $catChild->getId(),
                    'children' => [],
                    'sourceData' => []
                );
                $catBoxData[$catChild->getId()] = $this->createBoxPlotData($foos->getColor(), $foos->getName());
            }
            $ret[] = $tempArray;
        }

        $ret = array(
            "name" => "main",
            "color" => '#ffff',
            "children" => $ret
        );
        return $ret;
    }

    public function fillMainCategories()
    {


        foreach ($this->mainCats['children'] as $mainCatKey=>$mainCat) {

            foreach ($mainCat['children'] as $catChildKey => $catChild) {

                $this->mainCats['children'][$mainCatKey]['children'][$catChildKey]['sourceData'] = $this->getSourceData($mainCat['catid'], $catChild['catid']);
            }
        }


        return $this->mainCats;
    }


    public function getFullResults()
    {
        $this->initCatData(); //fetch all data at once
        return json_encode($this->fillMainCategories(), JSON_PRETTY_PRINT);
    }
}
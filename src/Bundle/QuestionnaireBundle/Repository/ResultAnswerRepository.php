<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\ResultAnswer;


/**
 * Class ResultRepository
 *
 * @author Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Repository
 *
 * @method ResultAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ResultAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ResultAnswer[]    findAll()
 * @method ResultAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResultAnswerRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResultAnswer::class);
    }

    public function getChartDataForHeader($userId): array
    {
        //TODO get the mean from all the result values
        return [];
    }

    public function getChartDataForContentByCategory($userId, $categoryId): array
    {
        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                c.color as categoryColor, 
                c.icon as categoryIcon, 
                q.id as questionnaireId, 
                q.name as questionnaireName,
                u.id as userId, 
                r.id as resultId,
                r.creation_date as creationDate,
                q.site_id as Site,
                qu.recommendation_id as recommendation,
                COUNT(DISTINCT r.id) as results, 
                COUNT(ra.id) as answers, 
                SUM(ra.value) AS sumOfValues,
                AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE u.id=' . $userId . ' 
            AND c.id= ' . $categoryId . ' 
            GROUP BY c.id, q.id
            ORDER BY c.position ASC, q.position ASC;';

        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        return $rawData;
    }

    public function getDownloads($userId, $categoryId)
    {

        $qb = $this->createQueryBuilder('ra');

        $result = $qb->innerJoin('ra.result', 'r')

            ->select('c.id AS categoryId','q.name AS questionnaireName', 'r.id AS resultId', 'r.creationDate AS creationDate')
            ->innerJoin('r.user', 'u')
            ->innerJoin('ra.question', 'que')
            ->innerJoin('que.questionGroup', 'qg')
            ->innerJoin('qg.questionnaire', 'q')
            ->innerJoin('q.category', 'c')
            ->where('c.id = :cId')
            ->andWhere('u.id = :uId')
            ->setParameter('uId', $userId)
            ->setParameter('cId', $categoryId)
            ->groupBy('r.id')
            ->orderBy('r.creationDate','DESC')
            ->getQuery()
            ->getArrayResult();

        return $result;


    }

    public function getChartDataForContentByQuestionnaire($schoolId, $questionnaireId): array
    {

        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                c.color as categoryColor, 
                c.icon as categoryIcon, 
                q.id as questionnaireId, 
                q.name as questionnaireName,
                u.id as userId, 
                r.id as resultId,
                r.creation_date as creationDate,
                q.site_id as Site,
                COUNT(DISTINCT u.id) AS users,
                qu.recommendation_id as recommendation,
                COUNT(DISTINCT r.id) as results, 
                COUNT(ra.id) as answers, 
                SUM(ra.value) AS sumOfValues,
                AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
            INNER JOIN school AS s ON s.id = u.school_id
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE s.id=' . $schoolId . ' 
            AND q.id= ' . $questionnaireId . ' 
            GROUP BY c.id, q.id
            ORDER BY c.position ASC, q.position ASC;';
        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        return $rawData;
    }

    public function getChartDataBySchoolByCategory($schoolId, $categoryId): array
    {
        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                c.color as categoryColor, 
                c.icon as categoryIcon, 
                q.id as questionnaireId, 
                q.name as questionnaireName,
                u.id as userId, 
                r.id as resultId,
                max(r.creation_date) as creationDate,
                q.site_id as Site,
                qu.recommendation_id as recommendation,
                COUNT(DISTINCT r.id) as results, 
                COUNT(DISTINCT u.id) as users,
                COUNT(ra.id) as answers, 
                SUM(ra.value) AS sumOfValues,
                AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
            INNER JOIN school AS s ON u.school_id = s.id
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE s.id=' . $schoolId . ' 
            AND c.id= ' . $categoryId . ' 
            GROUP BY q.id
            ORDER BY  c.position ASC, q.position ASC, r.creation_date desc;';


        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);

        return $rawData;
    }

    //questionnaires filled out by category
    public function getMeanForAllCategories($userId): array
    {
        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                q.name as questionnaireName,
                AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE u.id=' . $userId . ' 
            GROUP BY c.id, q.id
            ORDER BY c.position ASC, q.position ASC;';
        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        return $rawData;
    }

    //questionnaires filled out by the user order by category
    public function getMeanForAllCategoriesAllUsers($schoolId): array
    {
        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                q.name as questionnaireName,
                COUNT(DISTINCT(u.id)) AS users,
                SUM(ra.value) AS sumValues,
                COUNT(ra.id) AS questionsNumber,
                AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
                INNER JOIN school AS s ON u.school_id = s.id
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
                     WHERE s.id=' . $schoolId . ' 
            GROUP BY c.id, q.id
            ORDER BY c.position ASC, q.position ASC;';
        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        return $rawData;
    }

    //questionnaires filled out by the school and by category
    public function getMeanByCategoriesAllUsers($schoolId, $categoryId): array
    {
        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                q.name as questionnaireName,
                COUNT(DISTINCT(u.id)) AS users,
                SUM(ra.value) AS sumValues,
                COUNT(ra.id) AS questionsNumber,
                AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
                INNER JOIN school AS s ON u.school_id = s.id
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
                     WHERE s.id=' . $schoolId . '
                     AND c.id=' . $categoryId . ' 
            GROUP BY q.id
            ORDER BY q.position ASC;';
        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        return $rawData;
    }


    public function getRecommendationsByUser($userId, $categoryId): array
    {
        $recommandations = [];
        $catsarray = [];

        $conn = $this->getEntityManager()->getConnection();

        $where = ' and user_id= ' . $userId . '';


        //$this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE") // Der Schulträger

        $sql = 'Select questionnairebundle_result.id as resuId,recommendation_id,category_id,questionnairebundle_questionnaire.name as questName from questionnairebundle_result_answer
                    inner join questionnairebundle_result on questionnairebundle_result.id = questionnairebundle_result_answer.result_id
                    inner join publicuserbundle_user on publicuserbundle_user.id = user_id  
                    inner join  (select max(id) as foo from questionnairebundle_result group by user_id,questionnaire_id)  latest on latest.foo = questionnairebundle_result.id
                    inner join questionnairebundle_question on questionnairebundle_question.id = questionnairebundle_result_answer.question_id
                    inner join questionnairebundle_questionnaire  on questionnairebundle_result.questionnaire_id = questionnairebundle_questionnaire.id
                where questionnairebundle_result_answer.value <= 4
                    and questionnairebundle_question.recommendation_id >0
                   ' . $where . ' AND category_id=' . $categoryId . '
                group by  category_id,recommendation_id
                order by questionnairebundle_questionnaire.position asc,questionnairebundle_question.position asc               
                ';


        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();
        foreach ($ret as $retrow) {
            if (!isset($recommandations[$retrow['category_id']])) {
                $recommandations[$retrow['category_id']] = [];
            }
            $catsarray[$retrow['category_id']][$retrow['questName']][] = $retrow['recommendation_id'];
            $recommandations[] = $retrow['recommendation_id'];
        }


        $foo = $this->getEntityManager()->getRepository(Recommendation::class)->findBy(['id' => $recommandations]);
        $recommandations = [];
        foreach ($foo as $key => $rec) {
            $recommandations[$rec->getId()] = [
                'id' => $rec->getId(),
                'idDropdown' => $rec->getId() . '_' . $key,
                'title' => $rec->getTitle(),
                'description' => $rec->getDescription(),
                'implementation' => $rec->getImplementation(),
                'tips' => $rec->getTips(),
                'examples' => $rec->getExamples(),
                'additionalInformation' => $rec->getAdditionalInformation(),
                'tipsSchoolManagement' => $rec->getTipsSchoolManagement()
            ];
        }


        $retu = [];
        foreach ($catsarray as $cat => $catVal) {
            foreach ($catVal as $catrec => $va) {
                foreach ($va as $foi) {
                    $retu[$catrec][] = $recommandations[$foi];
                }
            }
        }
        return $retu;
    }


    public function getRecommendationsBySchoolByQuestionnaire($schoolId, $questionnaireId): array
    {
        $recommandations = [];
        $catsarray = [];

        $conn = $this->getEntityManager()->getConnection();
        $where = ' and school_id= ' . $schoolId . '';


        $sql = 'Select AVG(questionnairebundle_result_answer.value) as value, questionnairebundle_result.id as resuId,recommendation_id,category_id,questionnairebundle_questionnaire.name as questName from questionnairebundle_result_answer
                    inner join questionnairebundle_result on questionnairebundle_result.id = questionnairebundle_result_answer.result_id
                    inner join publicuserbundle_user on publicuserbundle_user.id = user_id  
                    inner join  (select max(id) as foo from questionnairebundle_result group by user_id,questionnaire_id)  latest on latest.foo = questionnairebundle_result.id
                    inner join questionnairebundle_question on questionnairebundle_question.id = questionnairebundle_result_answer.question_id
                    inner join questionnairebundle_questionnaire  on questionnairebundle_result.questionnaire_id = questionnairebundle_questionnaire.id
                where questionnairebundle_result_answer.value <= 4
                    and questionnairebundle_question.recommendation_id >0
                   ' . $where . ' AND questionnaire_id=' . $questionnaireId . '
                group by  category_id,recommendation_id
                order by questionnairebundle_questionnaire.position asc,questionnairebundle_question.position asc               
                ';


        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();

        foreach ($ret as $retrow) {
            if (!isset($recommandations[$retrow['category_id']])) {
                $recommandations[$retrow['category_id']] = [];
            }
            $catsarray[$retrow['category_id']][$retrow['questName']][] = $retrow['recommendation_id'];
            $recommandations[] = $retrow['recommendation_id'];
        }


        $foo = $this->getEntityManager()->getRepository(Recommendation::class)->findBy(['id' => $recommandations]);
        $recommandations = [];
        foreach ($foo as $key => $rec) {
            $recommandations[$rec->getId()] = [
                'id' => $rec->getId(),
                'idDropdown' => $rec->getId() . '_' . $key,
                'title' => $rec->getTitle(),
                'description' => $rec->getDescription(),
                'implementation' => $rec->getImplementation(),
                'tips' => $rec->getTips(),
                'examples' => $rec->getExamples(),
                'additionalInformation' => $rec->getAdditionalInformation(),
                'tipsSchoolManagement' => $rec->getTipsSchoolManagement()
            ];
        }


        $retu = [];
        foreach ($catsarray as $cat => $catVal) {
            foreach ($catVal as $catrec => $va) {
                foreach ($va as $foi) {
                    $retu[$catrec][] = $recommandations[$foi];
                }
            }
        }
        return $retu;
    }


    public function test($schoolId, $questionnaireId): array
    {
        $recommandations = [];
        $catsarray = [];

        $conn = $this->getEntityManager()->getConnection();
        $where = ' and school_id= ' . $schoolId . '';

        $sql = 'Select AVG(questionnairebundle_result_answer.value) as value, questionnairebundle_result.id as resuId,recommendation_id,category_id,questionnairebundle_questionnaire.name as questName from questionnairebundle_result_answer
                    inner join questionnairebundle_result on questionnairebundle_result.id = questionnairebundle_result_answer.result_id
                    inner join publicuserbundle_user on publicuserbundle_user.id = user_id  
                    inner join  (select max(id) as foo from questionnairebundle_result group by user_id,questionnaire_id)  latest on latest.foo = questionnairebundle_result.id
                    inner join questionnairebundle_question on questionnairebundle_question.id = questionnairebundle_result_answer.question_id
                    inner join questionnairebundle_questionnaire  on questionnairebundle_result.questionnaire_id = questionnairebundle_questionnaire.id
                where questionnairebundle_result_answer.value <= 4
                    and questionnairebundle_question.recommendation_id >0
                   ' . $where . ' AND questionnaire_id=' . $questionnaireId . '
                group by  category_id,recommendation_id
                order by questionnairebundle_questionnaire.position asc,questionnairebundle_question.position asc               
                ';


        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();



        foreach ($ret as $retrow) {
            if (!isset($recommandations[$retrow['category_id']])) {
                $recommandations[$retrow['category_id']] = [];
            }
            $catsarray[$retrow['category_id']][$retrow['questName']][] = $retrow['recommendation_id'];
            $recommandations[] = $retrow['recommendation_id'];
        }


        $foo = $this->getEntityManager()->getRepository(Recommendation::class)->findBy(['id' => $recommandations]);
        $recommandations = [];
        foreach ($foo as $key => $rec) {
            $recommandations[$rec->getId()] = [
                'id' => $rec->getId(),
                'idDropdown' => $rec->getId() . '_' . $key,
                'title' => $rec->getTitle(),
                'description' => $rec->getDescription(),
                'implementation' => $rec->getImplementation(),
                'tips' => $rec->getTips(),
                'examples' => $rec->getExamples(),
                'additionalInformation' => $rec->getAdditionalInformation(),
                'tipsSchoolManagement' => $rec->getTipsSchoolManagement()
            ];
        }


        $retu = [];
        foreach ($catsarray as $cat => $catVal) {
            foreach ($catVal as $catrec => $va) {
                foreach ($va as $foi) {
                    $retu[$catrec][] = $recommandations[$foi];
                }
            }
        }
        return $retu;
    }

    public function getRecommendationsBySchoolByQuestionnaires($schoolId, $arrayQuestionnairesIds): array
    {

        $recommandations = [];
        $catsarray = [];

        $conn = $this->getEntityManager()->getConnection();
        $where = ' and school_id= ' . $schoolId . '';

        //$this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE") // Der Schulträger

        $sql = 'Select AVG(questionnairebundle_result_answer.value) as value, questionnairebundle_result.id as resuId,recommendation_id,category_id,questionnairebundle_questionnaire.name as questName from questionnairebundle_result_answer
                    inner join questionnairebundle_result on questionnairebundle_result.id = questionnairebundle_result_answer.result_id
                    inner join publicuserbundle_user on publicuserbundle_user.id = user_id  
                    inner join  (select max(id) as foo from questionnairebundle_result group by user_id,questionnaire_id)  latest on latest.foo = questionnairebundle_result.id
                    inner join questionnairebundle_question on questionnairebundle_question.id = questionnairebundle_result_answer.question_id
                    inner join questionnairebundle_questionnaire  on questionnairebundle_result.questionnaire_id = questionnairebundle_questionnaire.id
                where questionnairebundle_result_answer.value <= 4
                    and questionnairebundle_question.recommendation_id >0
                   ' . $where . ' AND questionnaire_id IN(' . implode(',', $arrayQuestionnairesIds) . ')
                group by  category_id,recommendation_id
                order by questionnairebundle_questionnaire.position asc,questionnairebundle_question.position asc               
                ';


        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();

        foreach ($ret as $retrow) {
            if (!isset($recommandations[$retrow['category_id']])) {
                $recommandations[$retrow['category_id']] = [];
            }
            $catsarray[$retrow['category_id']][$retrow['questName']][] = $retrow['recommendation_id'];
            $recommandations[] = $retrow['recommendation_id'];
        }


        $foo = $this->getEntityManager()->getRepository(Recommendation::class)->findBy(['id' => $recommandations]);
        $recommandations = [];
        foreach ($foo as $key => $rec) {
            $recommandations[$rec->getId()] = [
                'id' => $rec->getId(),
                'idDropdown' => $rec->getId() . '_' . $key,
                'title' => $rec->getTitle(),
                'description' => $rec->getDescription(),
                'implementation' => $rec->getImplementation(),
                'tips' => $rec->getTips(),
                'examples' => $rec->getExamples(),
                'additionalInformation' => $rec->getAdditionalInformation(),
                'tipsSchoolManagement' => $rec->getTipsSchoolManagement()
            ];
        }


        $retu = [];
        foreach ($catsarray as $cat => $catVal) {
            foreach ($catVal as $catrec => $va) {
                foreach ($va as $foi) {
                    $retu[$catrec][] = $recommandations[$foi];
                }
            }
        }
        return $retu;
    }

    public function getRecommendationsBySchool($schoolId, $categoryId): array
    {
        $recommandations = [];
        $catsarray = [];

        $conn = $this->getEntityManager()->getConnection();
        $where = ' and school_id= ' . $schoolId . '';

        //$this->isGranted("ROLE_SCHOOL_AUTHORITY_LITE") // Der Schulträger

        $sql = 'Select questionnairebundle_result.id as resuId,recommendation_id,category_id,questionnairebundle_questionnaire.name as questName from questionnairebundle_result_answer
                    inner join questionnairebundle_result on questionnairebundle_result.id = questionnairebundle_result_answer.result_id
                    inner join publicuserbundle_user on publicuserbundle_user.id = user_id  
                    inner join  (select max(id) as foo from questionnairebundle_result group by user_id,questionnaire_id)  latest on latest.foo = questionnairebundle_result.id
                    inner join questionnairebundle_question on questionnairebundle_question.id = questionnairebundle_result_answer.question_id
                    inner join questionnairebundle_questionnaire  on questionnairebundle_result.questionnaire_id = questionnairebundle_questionnaire.id
                where questionnairebundle_result_answer.value <= 4
                    and questionnairebundle_question.recommendation_id >0
                   ' . $where . ' AND category_id=' . $categoryId . '
                group by  category_id,recommendation_id
                order by questionnairebundle_questionnaire.position asc,questionnairebundle_question.position asc               
                ';


        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();
        foreach ($ret as $retrow) {
            if (!isset($recommandations[$retrow['category_id']])) {
                $recommandations[$retrow['category_id']] = [];
            }
            $catsarray[$retrow['category_id']][$retrow['questName']][] = $retrow['recommendation_id'];
            $recommandations[] = $retrow['recommendation_id'];
        }


        $foo = $this->getEntityManager()->getRepository(Recommendation::class)->findBy(['id' => $recommandations]);
        $recommandations = [];
        foreach ($foo as $key => $rec) {
            $recommandations[$rec->getId()] = [
                'id' => $rec->getId(),
                'idDropdown' => $rec->getId() . '_' . $key,
                'title' => $rec->getTitle(),
                'description' => $rec->getDescription(),
                'implementation' => $rec->getImplementation(),
                'tips' => $rec->getTips(),
                'examples' => $rec->getExamples(),
                'additionalInformation' => $rec->getAdditionalInformation(),
                'tipsSchoolManagement' => $rec->getTipsSchoolManagement()
            ];
        }


        $retu = [];
        foreach ($catsarray as $cat => $catVal) {
            foreach ($catVal as $catrec => $va) {
                foreach ($va as $foi) {
                    $retu[$catrec][] = $recommandations[$foi];
                }
            }
        }
        return $retu;
    }

    //questionnaires filled out by the user order by category
    public function getRecommendationsByUser_OLD($userId, $categoryId): array
    {
        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                q.name as questionnaireName,
                qu.question,
                r.id,
                ra.value,
                reco.title
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
                INNER JOIN school AS s ON u.school_id = s.id
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_recommendation reco ON reco.id=qu.recommendation_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
                     WHERE u.id=' . $userId . ' AND c.id=' . $categoryId . '
            GROUP BY q.id
            ORDER BY c.position ASC, q.position ASC;';
        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        return $rawData;
    }

    public function getRecommendationsByUser__OLD($userId, $categoryId): array
    {
        $sql = 'SELECT q.id, reco.title, resu.id, ra.value FROM questionnairebundle_question AS ques
         INNER JOIN questionnairebundle_recommendation AS reco ON ques.recommendation_id=reco.id
         INNER JOIN questionnairebundle_question_group AS qg ON ques.question_group_id=qg.id
         INNER JOIN questionnairebundle_questionnaire AS q ON q.id=qg.questionnaire_id
         INNER JOIN questionnairebundle_result AS resu ON (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = ' . $userId . '  
                    ORDER BY creation_date DESC LIMIT 1)
         INNER JOIN questionnairebundle_result_answer AS ra ON ra.result_id=resu.id
         INNER JOIN publicuserbundle_user AS u ON resu.user_id=u.id
         INNER JOIN questionnairebundle_category AS c ON q.category_id=c.id
         WHERE u.id =' . $userId . ' AND c.id=' . $categoryId . ' GROUP BY q.id;
';

        $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        return $rawData;
    }


}

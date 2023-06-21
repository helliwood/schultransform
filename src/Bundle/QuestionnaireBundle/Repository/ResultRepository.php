<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;


/**
 * Class ResultRepository
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Repository
 *
 * @method Result|null find($id, $lockMode = null, $lockVersion = null)
 * @method Result|null findOneBy(array $criteria, array $orderBy = null)
 * @method Result[]    findAll()
 * @method Result[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResultRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    /**
     * @param User|null $user
     * @param Category $category
     * @return array
     */
    public function getResultsByUserAndCategory(?User $user, Category $category): array
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.questionnaire', 'q')
            ->innerJoin('q.category', 'c')
            ->where('c = :category')
            ->setParameter('category', $category)
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy("q.name", "ASC");

        return $qb->getQuery()->getResult();
    }

    public function getCategoriesFromQuestionnairesFilledOut($userId)
    {
        return $this->createQueryBuilder('r')
            ->select('c.id, q.name')
            ->innerJoin('r.user', 'u')
            ->innerJoin('r.questionnaire', 'q')
            ->innerJoin('q.category', 'c')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
//            ->groupBy('c.id')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function getQuestionnairesDone($userId)
    {
        return $this->createQueryBuilder('r')
            ->select('q.id,q.name')
            ->innerJoin('r.questionnaire', 'q')
            ->where('r.user = :userId')
            ->setParameter('userId', $userId)
            ->distinct()
            ->groupBy('q.id')
            ->getQuery()
            ->getResult();

    }

    public function getQuestionnairesDonePerCategoryAndUser($userId, $categoryId)
    {
        return $this->createQueryBuilder('r')
            ->select('q.id,q.name')
            ->innerJoin('r.questionnaire', 'q')
            ->innerJoin('q.category', 'c')
            ->where('r.user = :userId')
            ->andWhere('c.id = :catId')
            ->setParameter('userId', $userId)
            ->setParameter('catId', $categoryId)
            ->distinct()
            ->groupBy('q.id')
            ->getQuery()
            ->getResult();

    }

    public function getQuestionnairesIdsDonePerCategoryAndUser($userId, $categoryId)
    {
        return $this->createQueryBuilder('r')
            ->select('q.id')
            ->innerJoin('r.questionnaire', 'q')
            ->innerJoin('q.category', 'c')
            ->where('r.user = :userId')
            ->andWhere('c.id = :catId')
            ->setParameter('userId', $userId)
            ->setParameter('catId', $categoryId)
            ->distinct()
            ->groupBy('q.id')
            ->getQuery()
            ->getSingleColumnResult();

    }

    public function getTeachersActiveByCategory($categoryId, $schoolId)
    {
        return $this->createQueryBuilder('r')
            ->select('q.name', 'u.code')
            ->innerJoin('r.questionnaire', 'q')
            ->innerJoin('r.user', 'u')
            ->innerJoin('u.school', 's')
            ->innerJoin('q.category', 'c')
            ->where('s.id = :schoolId')
            ->andWhere('c.id = :catId')
            ->setParameter('catId', $categoryId)
            ->setParameter('schoolId', $schoolId)
            ->distinct()
            ->groupBy('u.code')
            ->getQuery()
            ->getResult();

    }

    /**
     * @throws NonUniqueResultException
     */
    public function getTeachersLastActivityDateByCategory($categoryId, $teachersCodesArray)
    {
        return $this->createQueryBuilder('r')
            ->select('r.creationDate')
            ->innerJoin('r.questionnaire', 'q')
            ->innerJoin('r.user', 'u')
            ->innerJoin('u.school', 's')
            ->innerJoin('q.category', 'c')
            ->where("u.code in('" . implode("', '", $teachersCodesArray) . "')")
            ->andWhere('c.id = :catId')
            ->setParameter('catId', $categoryId)
//            ->groupBy('c.id')
            ->orderBy('r.creationDate','DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

    }

    public function getFilledOutQuestionaires($userId)
    {
        return $this->createQueryBuilder('r')
            ->select('c.id as categoryID,q.name,q.icon,r.creationDate,q.id as questionairID')
            ->innerJoin('r.user', 'u')
            ->innerJoin('r.questionnaire', 'q')
            ->innerJoin('q.category', 'c')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.creationDate', 'DESC')
            ->groupBy('q.id')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function getTeachersRelatedWithQuestionnairesDone(int $schoolId)
    {

        return $this->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->innerJoin('u.school', 's')
            ->andWhere('s.id = :schoolId')
            ->andWhere('u.roles NOT LIKE :sLike')
            ->setParameter('schoolId', $schoolId)
            ->setParameter('sLike', '%SCHOOL%')
            ->distinct()
            ->groupBy('u.id')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getQuestionnairesFilledOutByCategory($schoolId, $categoryId): array
    {
        {
            $sql = '
            SELECT c.id as categoryId,
                q.icon,
                c.name as categoryName,
                q.name as name,
                q.id,
                u.id as userId, 
                r.id as resultId,
                r.creation_date as creationDate,
                qu.recommendation_id as recommendation,
                COUNT(DISTINCT r.id) as results, 
                COUNT(DISTINCT u.id) as users,
                COUNT(ra.id) as answers, 
                SUM(ra.value) AS sumOfValues,
                AVG(ra.value) as mean
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
            AND c.id =' . $categoryId . ' GROUP BY q.id
            ORDER BY c.position ASC, q.position ASC;';

            $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);

            return $rawData;
        }
    }

    public function getQuestionnairesFilledOut($schoolId): array
    {
        {
            $sql = '
            SELECT c.id as categoryId,
                q.icon,
                c.name as categoryName,
                q.name as name,
                q.id,
                u.id as userId, 
                r.id as resultId,
                r.creation_date as creationDate,
                qu.recommendation_id as recommendation,
                COUNT(DISTINCT r.id) as results, 
                COUNT(DISTINCT u.id) as users,
                COUNT(ra.id) as answers, 
                SUM(ra.value) AS sumOfValues,
                AVG(ra.value) as mean
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
            GROUP BY q.id
            ORDER BY c.position ASC, q.position ASC;';

            $rawData = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);

            return $rawData;
        }
    }

    public function getLastDateResult($userId,$questionnaireId)
    {
        $qb = $this->createQueryBuilder('r');

        $result = $qb->select('r.creationDate')
            ->where('r.user = :userId')
            ->andWhere('r.questionnaire = :qId')
            ->setParameter('userId', $userId)
            ->setParameter('qId', $questionnaireId)
            ->orderBy('r.creationDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $result;
    }

}

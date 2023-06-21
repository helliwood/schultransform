<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;


/**
 * Class QuestionnaireRepository
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Repository
 *
 * @method Questionnaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method Questionnaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method Questionnaire[]    findAll()
 * @method Questionnaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionnaireRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Questionnaire::class);
    }

    /**
     * @param Category $category
     * @param string   $sort
     * @param bool     $sortDesc
     * @param int      $page
     * @param int      $limit
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(Category $category, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["name", "type", "position"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "position";
        }
        $qb = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.category = :category')
            ->setParameter('category', $category);

        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('q')
            ->where('q.category = :category')
            ->setParameter('category', $category)
            ->groupBy('q')
            ->orderBy("q." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }


    public function getQuestionnairesFilledOut($schoolId)
    {

        $qb = $this->createQueryBuilder('q')
            ->innerJoin('q.results', 'r')
            ->innerJoin('q.questionGroups', 'qg')
            ->innerJoin('qg.questions', 'ques')
            ->innerJoin('ques.answers', 'a')
            ->innerJoin('ques.recommendation', 'rec')
            ->innerJoin('r.user', 'u')
            ->innerJoin('u.school', 'school')
            ->where('school.id = :schoolId')
            ->groupBy('q.id')
            ->setParameter('schoolId', $schoolId);

        return $qb->getQuery()->getResult();

    }
}
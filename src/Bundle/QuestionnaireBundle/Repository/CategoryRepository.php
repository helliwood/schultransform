<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use function Doctrine\ORM\QueryBuilder;


/**
 * Class CategoryRepository
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Repository
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param Category|null $parent
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(string $sort, bool $sortDesc, int $page, int $limit, ?Category $parent = null): array
    {
        $sortValues = ["name", "position"];

        if (!\in_array($sort, $sortValues)) {
            $sort = "position";
        }
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');
        if (!is_null($parent)) {
            $qb->where('c.parent = :parent')
                ->setParameter('parent', $parent);
        } else {
            $qb->where($qb->expr()->isNull('c.parent'));
        }
        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('c')
            ->groupBy('c')
            ->orderBy("c." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if (!is_null($parent)) {
            $qb->where('c.parent = :parent')
                ->setParameter('parent', $parent);
        } else {
            $qb->where($qb->expr()->isNull('c.parent'));
        }

        $items = $qb->getQuery()->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     */
    public function getResultsByCategoryAndUser($userId, $categoryId)
    {

        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.questionnaires', 'q')
            ->innerJoin('q.site', 's')
            ->innerJoin('q.questionGroups', 'qg')
            ->innerJoin('qg.questions', 'ques')
            ->innerJoin('ques.answers', 'a')
            ->innerJoin('ques.recommendation', 'rec')
            ->innerJoin('q.results', 'r')
            ->where('r.user = :userId')
            ->andWhere('c.id = :catId')
            ->setParameter('userId', $userId)
            ->setParameter('catId', $categoryId);

        return $qb->getQuery()->getOneOrNullResult();

    }

    /**
     */
    public function getResultsByCategoryForSchool($schoolId)
    {

        $qb = $this->createQueryBuilder('c')
            ->addSelect('(q.name) as name')
            ->addSelect('(q.category) as catId')
            ->addSelect('(q.category) as id')
            ->addSelect('(cate.icon) as icon')
            ->addSelect('count(u.id) as anzahl')
            ->addSelect('avg(r.rating) as rating')
            ->innerJoin('c.questionnaires', 'q')
            ->innerJoin('q.category', 'cate')
            ->innerJoin('q.results', 'r')
            ->innerJoin('r.user', 'u')
            ->where('u.school = :schoolId')
            ->groupBy('q.category')
            ->setParameter('schoolId', $schoolId);


        return $qb->getQuery()->getResult();

    }




}

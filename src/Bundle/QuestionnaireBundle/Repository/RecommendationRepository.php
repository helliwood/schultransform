<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use function Doctrine\ORM\QueryBuilder;


/**
 * Class RecommendationRepository
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Repository
 *
 * @method Recommendation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recommendation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recommendation[]    findAll()
 * @method Recommendation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecommendationRepository extends ServiceEntityRepository
{
    /**
     * RecommendationRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recommendation::class);
    }

    /**
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["title", "creationDate", "createdBy"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "title";
        }
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)');
        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('r')
            ->orderBy("r." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
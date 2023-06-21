<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionGroup;


/**
 * Class QuestionRepository
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Repository
 *
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    /**
     * QuestionRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * @param QuestionGroup $questionGroup
     * @param string        $sort
     * @param bool          $sortDesc
     * @param int           $page
     * @param int           $limit
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(QuestionGroup $questionGroup, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["question", "type", "position"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "position";
        }
        $qb = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.questionGroup = :questionGroup')
            ->setParameter('questionGroup', $questionGroup);

        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('q')
            ->where('q.questionGroup = :questionGroup')
            ->setParameter('questionGroup', $questionGroup)
            ->orderBy("q." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
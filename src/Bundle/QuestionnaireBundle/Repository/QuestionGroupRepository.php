<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionGroup;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;


/**
 * Class QuestionGroupRepository
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Repository
 *
 * @method QuestionGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuestionGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuestionGroup[]    findAll()
 * @method QuestionGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionGroupRepository extends ServiceEntityRepository
{
    /**
     * QuestionGroupRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionGroup::class);
    }

    /**
     * @param Questionnaire $questionnaire
     * @param string        $sort
     * @param bool          $sortDesc
     * @param int           $page
     * @param int           $limit
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(Questionnaire $questionnaire, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["name", "position"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "position";
        }
        $qb = $this->createQueryBuilder('qg')
            ->select('COUNT(qg.id)')
            ->where('qg.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire);

        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('qg')
            ->where('qg.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire)
            ->orderBy("qg." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
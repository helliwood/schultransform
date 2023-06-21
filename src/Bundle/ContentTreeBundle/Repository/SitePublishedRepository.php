<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SitePublished;

/**
 * Class SitePublishedRepository
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Repository
 *
 * @method SitePublished|null find($id, $lockMode = null, $lockVersion = null)
 * @method SitePublished|null findOneBy(array $criteria, array $orderBy = null)
 * @method SitePublished[]    findAll()
 * @method SitePublished[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SitePublishedRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SitePublished::class);
    }

    /**
     * @param SitePublished|null $parent
     * @return int|SitePublished
     */
    public function findBySitePositionAndParent(?SitePublished $parent)
    {
        return $this->createQueryBuilder('sp')
            ->innerJoin('sp.site', 's')
            ->where('sp.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy("s.position", "ASC")
            ->getQuery()
            ->getResult();
    }
}

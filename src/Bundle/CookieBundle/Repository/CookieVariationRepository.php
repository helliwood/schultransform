<?php

namespace Trollfjord\Bundle\CookieBundle\Repository;

use Trollfjord\Bundle\CookieBundle\Entity\CookieVariation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CookieVariation|null find($id, $lockMode = null, $lockVersion = null)
 * @method CookieVariation|null findOneBy(array $criteria, array $orderBy = null)
 * @method CookieVariation[]    findAll()
 * @method CookieVariation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CookieVariationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieVariation::class);
    }


    // /**
    //  * @return CookieItem[] Returns an array of CookieItem objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CookieVariation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

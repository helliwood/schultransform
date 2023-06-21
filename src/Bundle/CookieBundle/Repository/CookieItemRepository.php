<?php

namespace Trollfjord\Bundle\CookieBundle\Repository;

use Trollfjord\Bundle\CookieBundle\Entity\CookieItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CookieItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CookieItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CookieItem[]    findAll()
 * @method CookieItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CookieItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieItem::class);
    }


    public function findByParentArray(int $id)
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.cookieMain = :val')
            ->setParameter('val', $id)
            ->orderBy('ci.position', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    // /**
    //  * @return CookiItem[] Returns an array of CookiItem objects
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
    public function findOneBySomeField($value): ?CookiItem
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

<?php

namespace Trollfjord\Bundle\CookieBundle\Repository;

use Trollfjord\Bundle\CookieBundle\Entity\CookieMain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CookieMain|null find($id, $lockMode = null, $lockVersion = null)
 * @method CookieMain|null findOneBy(array $criteria, array $orderBy = null)
 * @method CookieMain[]    findAll()
 * @method CookieMain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CookieMainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieMain::class);
    }

    public function findAllArray()
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC')
            ->select('c', 'i')
            ->leftJoin('c.item', 'i')
            ->addOrderBy('i.position', 'ASC')
            ->getQuery()
            ->getArrayResult();

    }

    public function findOneArray(int $id)
    {
        $toReturn = $this->createQueryBuilder('c')
            ->where('c.id = :ID')
            ->setParameter('ID', $id)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        if (is_array($toReturn) && (count($toReturn) > 0)) {
            return $toReturn[0];
        } else {
            return [];
        }
    }

    public function getBanner($id)
    {

        $toReturn = $this->createQueryBuilder('c')
            ->select('c', 'i', 'civ')
            ->leftjoin('c.item', 'i')
            ->leftJoin('i.variations', 'civ')
            ->where('c.id = :ID')
            ->setParameter('ID', $id)
            ->orderBy('i.position', 'ASC')
            ->getQuery()
            ->getArrayResult();

        if (is_array($toReturn) && (count($toReturn) > 0)) {
            return $toReturn[0];
        } else {
            return [];
        }

    }

    // /**
    //  * @return CookieMain[] Returns an array of CookieMain objects
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
    public function findOneBySomeField($value): ?CookieMain
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

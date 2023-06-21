<?php

namespace Trollfjord\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Entity\SchoolAuthority;

/**
 * @method SchoolAuthority|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchoolAuthority|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchoolAuthority[]    findAll()
 * @method SchoolAuthority[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolAuthorityRepository extends ServiceEntityRepository
{
    /**
     * UserRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolAuthority::class);
    }

    /**
     * @param string $search
     * @return array|SchoolAuthority[]
     */
    public function findByPostalcode(string $search): array
    {
        if (empty($search)) {
            return [];
        }
        return $this->createQueryBuilder('sa')
            ->innerJoin('sa.address', 'a')
            ->where('a.postalcode LIKE :search OR a.country LIKE :search')
            ->setParameter('search', '%' . addcslashes($search, '%_') . '%')
            ->getQuery()
            ->getResult();
    }
}

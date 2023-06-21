<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteHistory;
use function in_array;

/**
 * Class SiteHistory
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Repository
 *
 * @method SiteHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SiteHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SiteHistory[]    findAll()
 * @method SiteHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SiteHistoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteHistory::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param Site   $site
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(Site $site, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["date", "action", "user"];
        if (! in_array($sort, $sortValues)) {
            $sort = "date";
        }

        $totalRows = $this->createQueryBuilder('sh')
            ->select('COUNT(sh.id)')
            ->where('sh.site = :site')
            ->setParameter('site', $site)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('sh')
            ->groupBy('sh')
            ->where('sh.site = :site')
            ->setParameter('site', $site)
            ->orderBy("sh." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}

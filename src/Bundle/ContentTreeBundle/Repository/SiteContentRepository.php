<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use function is_null;

/**
 * Class SiteContentRepository
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Repository
 *
 * @method SiteContent|null       find($id, $lockMode = null, $lockVersion = null)
 * @method SiteContent|null       findOneBy(array $criteria, array $orderBy = null)
 * @method SiteContent[]|array    findAll()
 * @method SiteContent[]|array    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SiteContentRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteContent::class);
    }

    /**
     * @param SiteContent $siteContent
     * @throws Exception
     */
    public function up(SiteContent $siteContent): void
    {
        if ($siteContent->getPosition() <= 1) {
            return;
        }
        $this->getEntityManager()->beginTransaction();
        try {
            $qb = $this->createQueryBuilder('sc');
            $qb->update()
                ->set('sc.position', 'sc.position+1')
                ->where('sc.site = :site')
                ->andWhere(is_null($siteContent->getParent()) ? $qb->expr()->isNull('sc.parent') : $qb->expr()->eq('sc.parent', ':parent'))
                ->andWhere(is_null($siteContent->getArea()) ? $qb->expr()->isNull('sc.area') : $qb->expr()->eq('sc.area', ':area'))
                ->andWhere('sc.position = :lower_position')
                ->setParameter('site', $siteContent->getSite())
                ->setParameter('lower_position', $siteContent->getPosition() - 1);
            if (! is_null($siteContent->getParent())) {
                $qb->setParameter('parent', $siteContent->getParent());
            }
            if (! is_null($siteContent->getArea())) {
                $qb->setParameter('area', $siteContent->getArea());
            }
            $qb->getQuery()->execute();

            $this->createQueryBuilder('sc')
                ->update()
                ->set('sc.position', 'sc.position-1')
                ->where('sc.id = :id')
                ->setParameter('id', $siteContent->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    /**
     * @param SiteContent $siteContent
     * @throws Exception
     */
    public function down(SiteContent $siteContent): void
    {
        if ((is_null($siteContent->getParent()) &&
                $siteContent->getPosition() >= $siteContent->getSite()->getContentByParentNull()->count()) ||
            ! is_null($siteContent->getParent()) && $siteContent->getPosition() >= $siteContent->getParent()->getChildrenByArea($siteContent->getArea())->count()) {
            return;
        }
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('sc')
                ->update()
                ->set('sc.position', 'sc.position+1')
                ->Where('sc.id = :id')
                ->setParameter('id', $siteContent->getId())
                ->getQuery()->execute();

            $qb = $this->createQueryBuilder('sc');
            $qb->update()
                ->set('sc.position', 'sc.position-1')
                ->where('sc.site = :site')
                ->andWhere(is_null($siteContent->getParent()) ? $qb->expr()->isNull('sc.parent') : $qb->expr()->eq('sc.parent', ':parent'))
                ->andWhere(is_null($siteContent->getArea()) ? $qb->expr()->isNull('sc.area') : $qb->expr()->eq('sc.area', ':area'))
                ->andWhere('sc.position = :greater_order')
                ->andWhere('sc.id != :id')
                ->setParameter('site', $siteContent->getSite())
                ->setParameter('greater_order', $siteContent->getPosition() + 1)
                ->setParameter('id', $siteContent->getId());
            if (! is_null($siteContent->getParent())) {
                $qb->setParameter('parent', $siteContent->getParent());
            }
            if (! is_null($siteContent->getArea())) {
                $qb->setParameter('area', $siteContent->getArea());
            }
            $qb->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }
}

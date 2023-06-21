<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContentData;
use function is_null;

/**
 * Class SiteContentDataRepository
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Repository
 *
 * @method SiteContentData|null find($id, $lockMode = null, $lockVersion = null)
 * @method SiteContentData|null findOneBy(array $criteria, array $orderBy = null)
 * @method SiteContentData[]    findAll()
 * @method SiteContentData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SiteContentDataRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteContentData::class);
    }

    /**
     * @param SiteContent          $siteContent
     * @param string               $key
     * @param SiteContentData|null $parent
     * @return SiteContentData
     */
    public function getOrCreate(SiteContent $siteContent, string $key, ?SiteContentData $parent = null): SiteContentData
    {
        $siteContentData = $this->findOneBy(['siteContent' => $siteContent, 'parent' => $parent, 'key' => $key]);
        if (is_null($siteContentData)) {
            $siteContentData = new SiteContentData();
            $siteContentData->setKey($key);
            $siteContentData->setParent($parent);
            $siteContentData->setSiteContent($siteContent);
        }
        return $siteContentData;
    }
}

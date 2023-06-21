<?php


namespace Trollfjord\Bundle\MediaBaseBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Meta;

/**
 * Class MediaRepository
 * @package Trollfjord\Bundle\MediaBaseBundle\Repository
 */
class MediaRepository extends ServiceEntityRepository
{
    /**
     * MediaRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function all(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.parent', 'ASC')
            ->getQuery()
            ->execute();
    }

    /**
     * @param Media|null $parent
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param string|null $filter
     * @param array|null $ext
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(
        ?Media  $parent,
        string  $sort,
        bool    $sortDesc,
        int     $page,
        int     $limit,
        ?string $filter = null,
        ?array  $ext = null): array
    {
        $sortValues = ["description", "name", "fileSize", "mimeType", "extension"];
        if (!\in_array($sort, $sortValues)) {
            $sort = "name";
        }
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');
        if ($parent) {
            $qb->where('m.parent = :parent')
                ->setParameter('parent', $parent);
        } else {
            $qb->where('m.parent IS NULL');
        }
        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();


        $qb = $this->createQueryBuilder('m');
        if ($parent) {
            $qb->where('m.parent = :parent')
                ->setParameter('parent', $parent);
        } else {
            $qb->where('m.parent IS NULL');
        }

        if ($filter !== null && strlen(trim($filter)) > 0) {
            $qb->andWhere(
                $qb->expr()->like('m.name', ':filter')
            )
                ->setParameter(':filter', '%' . $filter . '%');
        }

        if (is_array($ext)) {
            $_wherer = "m.extension = '" . implode("' or m.extension = '", $ext) . "' or m.isDirectory = 1";
            $qb->andWhere(
                $_wherer
            );
        }


        $items = $qb->groupBy('m')
            ->orderBy("m." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $tree = [];
        if ($parent) {
            $toParent = new Media();
            $toParent->setIsDirectory(true);
            $toParent->setId($parent->getId());
            $toParent->setName('..');
            $toParent->setMimeType('toParent');
            if ($parent->getParent()) {
                $toParent->setParent($parent->getParent());
            }
            $items = array_merge([$toParent], $items);

            $tree = [$parent];
            while ($parent = $parent->getParent()) {
                $tree[] = $parent;
            }
            $tree = array_reverse($tree);
        }

        return ["totalRows" => $totalRows, "items" => $items, "tree" => $tree];
    }

    /**
     * @param Media $media
     * @return array
     */
    public function getMetasAsKeyArray(Media $media): array
    {
        $arr = [];
        $metas = $media->getMetas();
        if ($metas) {
            /** @var Meta $meta */
            foreach ($metas as $meta) {
                $arr[$meta->getName()] = $meta;
            }
        }
        return $arr;
    }

    public function searchByWord($word, $extensionArray = null): array
    {
        $arrayToReturn = [];
        $builder = $this->createQueryBuilder('m')
            ->select('m', 'p')
            ->andWhere('m.name LIKE :wordTerm')
            ->leftJoin('m.parent', 'p')
            ->setParameter('wordTerm', '%' . $word . '%');

        if ($extensionArray && (is_array($extensionArray))) {
            $builder->andWhere("m.extension = '" . implode("' or m.extension = '", $extensionArray)."'");
        }
        $items = $builder->getQuery()->getResult();

        foreach ($items as $item) {
            if ($item->getParent()) {
                if ($item->getExtension() && $item->getParent()->getIsDirectory()) {
                    $arrayToReturn [] = $item;
                }
            }
            if ($item->getIsDirectory()) {
                $arrayToReturn [] = $item;
            }
        }
        return $arrayToReturn;
    }

}
<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Snippet;
use function array_intersect;
use function in_array;

/**
 * Class SnippetRepository
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Repository
 *
 * @method Snippet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Snippet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Snippet[]    findAll()
 * @method Snippet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SnippetRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Snippet::class);
    }

    /**
     * @param string[] $snippetIds
     * @return array|Snippet[]
     */
    public function findWhereNotInList(array $snippetIds): array
    {
        return $this->createQueryBuilder("s")
            ->where("s.removed = false")
            ->andWhere("s.id NOT IN (:ids)")
            ->setParameter("ids", $snippetIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $allowedGroups
     * @return Snippet[]
     */
    public function findByAllowedGroups(array $allowedGroups): array
    {
        /** @var Snippet[] $snippets */
        $snippets = $this->createQueryBuilder("s")
            ->where("s.removed = false")
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
        $result = [];
        foreach ($snippets as $snippet) {
            if (! empty(array_intersect($allowedGroups, $snippet->getGroups()))) {
                $result[] = $snippet;
            }
        }
        return $result;
    }

    /**
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @return string[]
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["id", "name", "groups", "uses"];

        if (! in_array($sort, $sortValues)) {
            $sort = "id";
        }
        $totalRows = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')->getQuery()
            ->getSingleScalarResult();


        $items = $this->createQueryBuilder('s')
            ->select('s, COUNT(sc.id) as HIDDEN uses')
            ->leftJoin('s.content', 'sc')
            ->groupBy('s')
            ->orderBy("s." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        if ($sort === "uses") {
            $items->orderBy('uses', $sortDesc ? 'DESC' : 'ASC');
        }
        $items = $items->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}

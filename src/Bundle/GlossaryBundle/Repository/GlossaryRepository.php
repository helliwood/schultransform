<?php

namespace Trollfjord\Bundle\GlossaryBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\GlossaryBundle\Entity\Glossary;

class GlossaryRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Glossary::class);
    }

    /**
     * @param int $letterGroupId
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param string|null $filter
     * @return array
     */
    public function find4Ajax(
        int     $letterGroupId,
        string  $sort,
        bool    $sortDesc,
        int     $page,
        int     $limit,
        ?string $filter = null): array
    {

        $gb = $this->createQueryBuilder('g')
            ->where('g.letterGroup = :letterGroup')
            ->setParameter(':letterGroup', $letterGroupId)
            ->getQuery()
            ->getResult();
        if (!empty($gb)) {
            return ["totalRows" => count($gb), "items" => $gb];
        } else {
            return [];
        }

    }

    public function searchWord($word): array
    {
        $gb = $this->createQueryBuilder('g')
            ->where('g.word = :gword')
            ->setParameter(':gword', $word)
            ->getQuery()
            ->getResult();
        if (!empty($gb)) {
            return $gb;
        } else {
            return [];
        }

    }

    public function searchWordAutocomplete($word): array
    {
        $gb = $this->createQueryBuilder('g')
            ->where('g.word LIKE :gword')
            ->setParameter(':gword', $word . '%')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
        if (!empty($gb)) {
            return $gb;
        } else {
            return [];
        }

    }

    public function searchWordAutocompleteRaw($word): array
    {
        $tableName = 'glossarybundle_glossary';
        try {
            $tableName = $this->getClassMetadata()->getMetadataValue('table')['name'];
        } catch (Exception $e) {

        }

        $conn = $this->getEntityManager()
            ->getConnection();

        $sql = "SELECT * FROM $tableName WHERE word LIKE ? COLLATE utf8mb4_german2_ci LIMIT 8";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $word . '%');
        $stmt->execute();
        $gb = $stmt->fetchAll();

        if (!empty($gb)) {
            return $gb;
        } else {
            return [];
        }

    }

    public function getCommentPaginator(int $letterGroup, int $offset, int $itemsPerPage): Paginator
    {
        $query = $this->createQueryBuilder('g')
            ->andWhere('g.letterGroup = :letterGroup')
            ->setParameter(':letterGroup', $letterGroup)
            ->setMaxResults($itemsPerPage)
            ->setFirstResult($offset)
            ->getQuery();
        return new Paginator($query);
    }


}
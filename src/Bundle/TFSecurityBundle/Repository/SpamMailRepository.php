<?php

namespace Trollfjord\Bundle\TFSecurityBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\TFSecurityBundle\Entity\SpamMail;
use function Doctrine\ORM\QueryBuilder;


/**
 * Class SpamMailRepository
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\TFSecurityBundle\Repository
 *
 * @method SpamMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method SpamMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method SpamMail[]    findAll()
 * @method SpamMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpamMailRepository extends ServiceEntityRepository
{
    /**
     * SpamMailRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpamMail::class);
    }

    /**
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param Category|null $parent
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(string $sort, bool $sortDesc, int $page, int $limit): array
    {

        $sortValues = [ "creation_date"];

        if (!\in_array($sort, $sortValues)) {
            $sort = "creationDate";
        }
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');

           // $qb->where($qb->expr()->isNull('c.parent'));

        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('c')
            ->groupBy('c')
            ->orderBy("c." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);


//            $qb->where($qb->expr()->isNull('c.parent'));

        $items = $qb->getQuery()->getResult();

        foreach ($items as $item){
            $retItems[]=array('id'=>$item->getId(),'emailAddress'=>$item->getEmailAddress(),'body'=>$item->getBody(),'subject'=>$item->getSubject(),'creationDate'=>$item->getCreationDate(),'status'=>$item->getStatus(),'_showDetails'=>false);
        }

        return ["totalRows" => $totalRows, "items" => $retItems];
    }

    /**
     */
    public function getResultsByCategoryAndUser($userId, $categoryId)
    {

        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.questionnaires', 'q')
            ->innerJoin('q.site', 's')
            ->innerJoin('q.questionGroups', 'qg')
            ->innerJoin('qg.questions', 'ques')
            ->innerJoin('ques.answers', 'a')
            ->innerJoin('ques.recommendation', 'rec')
            ->innerJoin('q.results', 'r')
            ->where('r.user = :userId')
            ->andWhere('c.id = :catId')
            ->setParameter('userId', $userId)
            ->setParameter('catId', $categoryId);

        return $qb->getQuery()->getOneOrNullResult();

    }

    /**
     */
    public function getResultsByCategoryForSchool($schoolId)
    {

        $qb = $this->createQueryBuilder('c')
            ->addSelect('(q.name) as name')
            ->addSelect('(q.category) as catId')
            ->addSelect('(q.category) as id')
            ->addSelect('(cate.icon) as icon')
            ->addSelect('count(u.id) as anzahl')
            ->addSelect('avg(r.rating) as rating')
            ->innerJoin('c.questionnaires', 'q')
            ->innerJoin('q.category', 'cate')
            ->innerJoin('q.results', 'r')
            ->innerJoin('r.user', 'u')
            ->where('u.school = :schoolId')
            ->groupBy('q.category')
            ->setParameter('schoolId', $schoolId);


        return $qb->getQuery()->getResult();

    }




}

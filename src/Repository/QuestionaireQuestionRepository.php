<?php
namespace Trollfjord\Repository;

use Trollfjord\Entity\QuestionaireQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method MyEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyEntity[]    findAll()
 * @method MyEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionaireQuestionRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionaireQuestion::class);
    }


    public function getAvg(array $references,string $formID,$userToken){



        $foo=$this->createQueryBuilder('qq');
        $foo->select('AVG(qq.scale_val)')
            ->where("qq.answerText='' ")
            ->andWhere("qq.reference in('" . implode("', '", $references) . "')")
            ->andWhere("qq.description='$formID'");
        if($userToken){
            $foo->andWhere("qq.usertoken='$userToken'");
        }
        $query= $foo->getQuery();
        $arrs=$query->getResult();
        return 0+$arrs[0]['1'];



    }
    // /**
    //  * @return MyEntity[] Returns an array of MyEntity objects
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
    public function findOneBySomeField($value): ?MyEntity
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
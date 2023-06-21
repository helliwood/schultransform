<?php

namespace Trollfjord\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Entity\QuestionaireQuestion;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    /**
     * UserRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param string $username
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function loadUserByUsername($username): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :email')
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $token
     * @return User
     * @throws NonUniqueResultException
     */
    public function findUserByToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('MD5(CONCAT(u.email,u.createdAt)) = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

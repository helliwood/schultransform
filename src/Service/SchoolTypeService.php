<?php

namespace Trollfjord\Service;


use Doctrine\ORM\EntityManagerInterface;
use Trollfjord\Entity\SchoolType;

class SchoolTypeService
{

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array|object[]|SchoolType[]
     */
    public function getSchoolTypes()
    {
        return $this->entityManager->getRepository(SchoolType::class)->findBy([], ['position' => 'ASC']);
    }

}
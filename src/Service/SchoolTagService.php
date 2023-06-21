<?php

namespace Trollfjord\Service;


use Doctrine\ORM\EntityManagerInterface;
use Trollfjord\Entity\SchoolTag;

class SchoolTagService
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
     * @return array|object[]|SchoolTag[]
     */
    public function getSchoolTags()
    {
        return $this->entityManager->getRepository(SchoolTag::class)->findBy([], ['id' => 'ASC']);
    }

}

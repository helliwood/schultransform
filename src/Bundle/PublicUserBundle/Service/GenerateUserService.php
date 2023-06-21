<?php

namespace Trollfjord\Bundle\PublicUserBundle\Service;


use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;
use Trollfjord\Entity\SchoolType;
use function is_null;
use function strtoupper;

class GenerateUserService
{

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string      $schoolType
     * @param string|null $schoolCode
     * @return User
     * @throws Exception
     */
    public function getNewUser(string $schoolType, ?string $schoolCode = null): User
    {
        $user = new User();
        $user->setSchoolType($this->entityManager->find(SchoolType::class, $schoolType));
        $user->setCode($this->getNewCode());
        if (! is_null($schoolCode)) {
            $school = $this->entityManager->getRepository(School::class)->findOneBy(['code' => $schoolCode]);
            $user->setSchool($school);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush($user);
        return $user;
    }

    /**
     * @return string
     */
    protected function createCode(): string
    {
        return strtoupper($this->getHash()) . "-" . strtoupper($this->getHash());
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getNewCode(): string
    {
        $try = 0;
        while (true) {
            $try++;
            $code = $this->createCode();
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['code' => $code]);
            if (! $user) {
                break;
            }
            if ($try > 1000) throw new Exception("Can't find a new User-Code!");
        }
        return $code;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getNewSchoolCode(): string
    {
        $try = 0;
        while (true) {
            $try++;
            $code = 'SCH-' . $this->createCode();
            $user = $this->entityManager->getRepository(School::class)->findOneBy(['code' => $code]);
            if (! $user) {
                break;
            }
            if ($try > 1000) throw new Exception("Can't find a new User-Code!");
        }
        return $code;
    }


    /**
     * @return string
     * @throws Exception
     */
    public function getNewSchoolAuthorityCode(): string
    {
        $try = 0;
        while (true) {
            $try++;
            $code = 'ST-' . $this->createCode();
            $user = $this->entityManager->getRepository(SchoolAuthority::class)->findOneBy(['code' => $code]);
            if (! $user) {
                break;
            }
            if ($try > 1000) throw new Exception("Can't find a new User-Code!");
        }
        return $code;
    }

    /**
     * @param int $j
     * @return string
     */
    private function getHash(int $j = 4): string
    {
        $string = "";
        for ($i = 0; $i < $j; $i++) {
            $x = mt_rand(0, 2);
            switch ($x) {
                case 0:
                    $char = chr(mt_rand(97, 122));
                    while ($char === 'i' || $char === 'l' || $char === 'o') {
                        $char = chr(mt_rand(97, 122));
                    }
                    $string .= $char;
                    break;
                case 1:
                    $char = chr(mt_rand(65, 90));
                    while ($char === 'I' ||  $char === 'O' || $char === 'L') {
                        $char = chr(mt_rand(65, 90));
                    }
                    $string .= $char;
                    break;
                case 2:
                    $char = chr(mt_rand(50, 57));
                    while ($char === '0') {
                        $char = chr(mt_rand(48, 57));
                    }
                    $string .= $char;
                    break;
            }
        }
        return $string;
    }

}

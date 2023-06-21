<?php

namespace Trollfjord\Bundle\PublicUserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;

use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;
use function in_array;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByCode(string $code)
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM ' . User::class . ' u
                WHERE u.code = ":query"'
        )
            ->setParameter('query', $code)
            ->getOneOrNullResult();
    }

    /**
     * @param string $email
     * @return int|mixed|string|null
     * @throws NonUniqueResultException
     */
    public function loadUserByEmail(string $email)
    {
        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :email')
            ->setParameter('email', $email)
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
            ->where('u.resetPasswordHash = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param string|null $filter
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(
        string  $sort,
        bool    $sortDesc,
        int     $page,
        int     $limit,
        ?string $filter = null): array
    {
        if ($sort === 'displayName') {
            $sort = "email";
        }
        $sortValues = ["id", "code", "username", "email"];
        if (!\in_array($sort, $sortValues)) {
            $sort = "code";
        }
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        $totalRows = $qb->getQuery()
            ->getSingleScalarResult();


        $qb = $this->createQueryBuilder('u');
        if ($filter !== null && strlen(trim($filter)) > 0) {
            $qb->leftJoin('u.school', 's');

            $qb->andWhere(
                $qb->expr()->like('u.username', ':filter'),
            )->orWhere(
                $qb->expr()->like('u.email', ':filter')
            )->orWhere(
                $qb->expr()->like('u.code', ':filter')
            )->orWhere(
                $qb->expr()->like('s.name', ':filter')
            )
                ->setParameter(':filter', '%' . $filter . '%');

            $items = $qb->groupBy('u')
                ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $totalRows = count($items);

        } else {

            $items = $qb->groupBy('u')
                ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();


        }

        for ($i = 0; $i < count($items); $i++) {
            $items[$i]->getCode();
            $items[$i]->getUserName();
            $items[$i]->getEmail();
            if ($items[$i]->getSchool()) {
                $items[$i]->getSchool()->getUsers();
                //count the questionnaires filled out
                //1.- get the if the school has teacher's codes
                //2.- check how many questionnaires were for each user filled out
                $schoolQuestionnairesFilledOut = [];
                if (!empty($items[$i]->getSchool()->getUsersRelated())) {

                    //count questionnaires filled out for every user
                    foreach ($items[$i]->getSchool()->getUsersRelated() as $userId) {
                        $schoolQuestionnairesFilledOut[] = $this->countQuestionnairesFilledOut($userId);
                    }
                    $items[$i]->getSchool()->setQuestionnairesFilledOut(array_sum($schoolQuestionnairesFilledOut));
                }
            }
            if ($items[$i]->getSchoolAuthority()) {
                $items[$i]->getSchoolAuthority()->getSchools();
            }
        }
        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param string $userType
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param string|null $filter
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4AjaxSortedBy(
        string  $userType,
        string  $sort,
        bool    $sortDesc,
        int     $page,
        int     $limit,
        ?string $filter = null): array
    {

        //defining fallback variables
        $totalRows = 0;
        $items = [];

        $qb = $this->createQueryBuilder('u')
            ->groupBy('u');
        $resultTemp = null;
        $resultTempCount = null;
        $userTypeCondition = null;

        //allow to perform the search function from the UserTable component
        if ($filter === null) {
            switch ($userType) {
                case 'teacher':

                    $userTypeCondition = "u.roles NOT LIKE '%ROLE_SCHOOL%'";
                    $qb->where($userTypeCondition);
                    if ($sort !== 'id') {
                        $sort = 'code';
                    }

                    break;
                case 'teacherWithSchool':

                    $userTypeCondition = "u.roles NOT LIKE '%ROLE_SCHOOL%' AND u.school IS NOT NULL";
                    $qb->where($userTypeCondition);
                    if ($sort !== 'id') {
                        $sort = 'code';
                    }
                    break;
                case 'teacherNoSchool':

                    $userTypeCondition = "u.roles NOT LIKE '%ROLE_SCHOOL%' AND u.school IS NULL";
                    $qb->where($userTypeCondition);
                    if ($sort !== 'id') {
                        $sort = 'code';
                    }
                    break;
                case 'school':
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
                    $qb->where($userTypeCondition);
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    break;
                case 'schoolWithTeachers':
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
                    $qb->where($userTypeCondition);
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    break;
                case 'testSchools':
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
                    $resultTemp = $qb->innerJoin('u.school', 's')
                        ->where($userTypeCondition)
                        ->andWhere('s.testSchool = 1')
                        ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                        ->getQuery()
                        ->getResult();
                    $resultTempCount = count($resultTemp);

                    break;
                case 'testSchoolAuthorities':
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY_LITE\"%'";
                    $resultTemp = $qb->innerJoin('u.schoolAuthority', 'sa')
                        ->where($userTypeCondition)
                        ->andWhere('sa.testSchoolAuthority = 1')
                        ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                        ->getQuery()
                        ->getResult();
                    $resultTempCount = count($resultTemp);
                    break;
                case strpos($userType, 'school_id_'):

                    $id = (int)preg_replace('/school_id_/', '', $userType);
                    $userTypeCondition = "u.roles NOT LIKE '%ROLE_SCHOOL%' AND u.school = $id";
                    $qb->where($userTypeCondition);
                    if ($sort !== 'id') {
                        $sort = 'code';
                    }
                    break;
                case strpos($userType, 'school_authority_id_'):

                    $id = (int)preg_replace('/school_authority_id_/', '', $userType);
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    $resultTemp = $qb->select('u')
                        ->innerJoin('u.school', 's')
                        ->where($userTypeCondition)
                        ->andWhere('s.schoolAuthority = :id')
                        ->setParameter('id', $id)
                        ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                        ->getQuery()
                        ->getResult();
                    $resultTempCount = count($resultTemp);
                    break;
                case strpos($userType, 'user_with_authority_id_'):

                    $idsRaw = preg_replace('/user_with_authority_id_/', '', $userType);
                    $id = explode(',', $idsRaw);
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY_LITE\"%'";
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    $resultTemp = $qb->select('u')
                        ->innerJoin('u.schoolAuthority', 'sa')
                        ->where($userTypeCondition)
                        ->andWhere('sa.id = :id')
                        ->setParameter('id', $id[1])
                        ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                        ->getQuery()
                        ->getResult();
                    $userOnSideBar = $this->find($id[0]);
                    $resultTemp[] = $userOnSideBar;
                    $resultTempCount = count($resultTemp);
                    break;
                case strpos($userType, 'user_with_school_id_'):
                    $idsRaw = preg_replace('/user_with_school_id_/', '', $userType);
                    $id = explode(',', $idsRaw);
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
                    if ($sort !== 'id') {
                        $sort = 'code';
                    }
                    $resultTemp = $qb->select('u')
                        ->innerJoin('u.school', 's')
                        ->where($userTypeCondition)
                        ->andWhere('s.id = :id')
                        ->setParameter('id', $id[1])
                        ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                        ->getQuery()
                        ->getResult();
                    $userOnSideBar = $this->find($id[0]);
                    $resultTemp[] = $userOnSideBar;
                    $resultTempCount = count($resultTemp);
                    break;
                case strpos($userType, 'users_authority_id_'):
                    $idsRaw = preg_replace('/users_authority_id_/', '', $userType);
                    $id = explode(',', $idsRaw);
                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    $resultTemp = $qb->select('u')
                        ->innerJoin('u.school', 's')
                        ->where($userTypeCondition)
                        ->andWhere('s.schoolAuthority = :id')
                        ->setParameter('id', $id[0])
                        ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                        ->getQuery()
                        ->getResult();

                    $schoolAuthority = $this->find($id[1]);
                    $resultTemp[] = $schoolAuthority;
                    $resultTempCount = count($resultTemp);
                    break;
                case strpos($userType, 'users_school_id_'):
                    $idsRaw = preg_replace('/users_school_id_/', '', $userType);
                    $id = explode(',', $idsRaw);
                    $userTypeCondition = "u.roles NOT LIKE '%ROLE_SCHOOL%'";
                    if ($sort !== 'id') {
                        $sort = 'code';
                    }

                    $resultTemp = $qb->select('u')
                        ->innerJoin('u.school', 's')
                        ->where($userTypeCondition)
                        ->andWhere('s.id = :id')
                        ->setParameter('id', $id[0])
                        ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                        ->getQuery()
                        ->getResult();

                    $schoolAuthority = $this->find($id[1]);
                    $resultTemp[] = $schoolAuthority;
                    $resultTempCount = count($resultTemp);

                    break;
                case 'schoolAuthority':

                    $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY_LITE\"%'";
                    $qb->where($userTypeCondition);
                    if ($sort !== 'id') {
                        $sort = 'email';
                    }
                    break;
            }


            $qbTotalRows = $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where($userTypeCondition);


        } else {
            $qbTotalRows = $this->createQueryBuilder('u')
                ->select('COUNT(u.id)');
        }


        if ($userTypeCondition) {
            $totalRows = $qbTotalRows->getQuery()
                ->getSingleScalarResult();

            if ($filter !== null && strlen(trim($filter)) > 0) {
                $qb->andWhere(
                    $qb->expr()->like('u.username', ':filter'),
                )->orWhere(
                    $qb->expr()->like('u.email', ':filter')
                )->orWhere(
                    $qb->expr()->like('u.code', ':filter')
                )
                    ->setParameter(':filter', '%' . $filter . '%');
                if ($sort === 'displayName') {
                    $sort = "email";
                }

                $sortValues = ["id", "code", "username", "email"];
                if (!in_array($sort, $sortValues)) {
                    $sort = "code";
                }
                $items = $qb->groupBy('u')
                    ->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                    ->setFirstResult(($page - 1) * $limit)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
                $totalRows = count($items);

            } else {
                $qb->orderBy("u." . $sort, $sortDesc ? 'DESC' : 'ASC')
                    ->setFirstResult(($page - 1) * $limit);

                //in case the relationship is deeper,
                // the limit should not be fixed because only the results of
                // this limit will be applied in the next step.
                if ($userType !== 'schoolWithTeachers') {
                    $qb->setMaxResults($limit);
                }

                $items = $qb->getQuery()
                    ->getResult();
            }

            //results can come from the switch function because of the complexity of the query
            $items = $resultTemp ?: $items;
            $totalRows = is_integer($resultTempCount) ?(int)$resultTempCount: $totalRows;
            //Variable to storage items to return
            $tempItemsArray = [];
            for ($i = 0; $i < count($items); $i++) {
                //To fill up the value of the 'displayName' of the User entity
                $items[$i]->getCode();
                $items[$i]->getUserName();
                $items[$i]->getEmail();
                if ($items[$i]->getSchoolAuthority()) {
                    //used to retrieve the number of schools that belongs to the school authority
                    //there is a property in the SchoolAuthority entity to storage this value
                    $items[$i]->getSchoolAuthority()->getSchools();
                }
                if ($items[$i]->getSchool()) {
                    //used to retrieve the number of teachers that belongs to the school
                    //there is a property in the School entity to storage this value
                    $items[$i]->getSchool()->getUsers();

                    //count the questionnaires filled out
                    //1.- get the if the school has teacher's codes
                    //2.- check how many questionnaires were for each user filled out
                    $schoolQuestionnairesFilledOut = [];
                    if (!empty($items[$i]->getSchool()->getUsersRelated())) {
                        //count questionnaires filled out for every user
                        foreach ($items[$i]->getSchool()->getUsersRelated() as $userId) {
                            $schoolQuestionnairesFilledOut[] = $this->countQuestionnairesFilledOut($userId);
                        }
                        $items[$i]->getSchool()->setQuestionnairesFilledOut(array_sum($schoolQuestionnairesFilledOut));
                    }

                    //query only schools with teachers
                    if ($items[$i]->getSchool()->getUserCount() > 0 && $userType === 'schoolWithTeachers') {
                        $tempItemsArray[] = $items[$i];
                    }
                }
            }
            //set the values if the array is not empty
            if (!empty($tempItemsArray)) {
                $items = (array)$tempItemsArray;
                $totalRows = count($tempItemsArray);
            }

        }

        return ["totalRows" => $totalRows, "items" => $items];
    }


    /**
     * @param $items
     * @return int
     */
    private function getSchoolData($items): int
    {
        if (empty($items)) {
            return 0;
        }
        //Variable to storage items to return
        $tempItemsArray = [];
        $toReturn = 0;

        for ($i = 0; $i < count($items); $i++) {
            if ($items[$i]->getSchool()) {
                //used to retrieve the number of teachers that belongs to the school
                //there is a property in the School entity to storage this value
                $items[$i]->getSchool()->getUsers();

                //query only schools with teachers
                if ($items[$i]->getSchool()->getUserCount() > 0) {
                    $tempItemsArray[] = $items[$i];
                }
            }
        }

        //set the values if the array is not empty
        if (!empty($tempItemsArray)) {
            $toReturn = count($tempItemsArray);
        }
        return $toReturn;
    }

    /**
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function sortAndCountUsers(): array
    {
        $conn = $this->getEntityManager()
            ->getConnection();

        $tableName = 'publicuserbundle_user';
        try {
            $tableName = $this->getClassMetadata()->getTableName();
        } catch (\Exception $exception) {

        }

        $sql = "SELECT 
                COUNT(CASE WHEN u.roles NOT LIKE '%SCHOOL%' THEN 1 END) as teacher,
                COUNT(CASE WHEN u.roles NOT LIKE '%SCHOOL%' AND u.school_id IS NULL THEN 1 END) as teacherNoSchool,
                COUNT(CASE WHEN u.roles NOT LIKE '%SCHOOL%' AND u.school_id IS NOT NULL THEN 1 END) as teacherWithSchool,
                COUNT(CASE WHEN u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%' THEN 1 END) as school,
                COUNT(CASE WHEN u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY_LITE\"%' THEN 1 END) as schoolAuthority
                FROM " . $tableName . " AS u;";
        $stmt = $conn->prepare($sql);
        $toReturn = $stmt->executeQuery()->fetchAllAssociative();


        //get the schools with teachers count
        $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
        $stmtSchoolWithTeachers = $this->createQueryBuilder('u')
            ->where($userTypeCondition)
            ->getQuery()
            ->getResult();
        $toReturn[0]['schoolWithTeachers'] = $this->getSchoolData($stmtSchoolWithTeachers);

        //test school count
        $toReturn[0]['testSchools'] = $this->getCountTestSchools();
        //test school authority count
        $toReturn[0]['testSchoolAuthorities'] = $this->getCountTestSchoolsAuthority();

        return $toReturn;
    }

    /**
     * @param $userId
     * @return int
     */
    private function countQuestionnairesFilledOut($userId): int
    {
        //get the Result repo to query
        $builder = $this->getEntityManager()->getRepository(Result::class)->createQueryBuilder('r');
        $result =
            $builder->select('q.name')
                ->innerJoin('r.questionnaire', 'q')
                ->where("r.user = $userId")
                ->groupBy('q.id')
                ->getQuery()
                ->getArrayResult();
        return count($result);
    }

    /**
     * @param $userId
     * @return float|int|array|string
     */
    public function getQuestionnairesFilledOut($userId)
    {
        $builder = $this->getEntityManager()->getRepository(Questionnaire::class)->createQueryBuilder('q');
        $result = $builder
            ->innerJoin('q.results', 'r')
            ->where("r.user = $userId")
            ->groupBy('q.id')
            ->getQuery()
            ->getArrayResult();
        return $result;
    }

    public function getCountTestSchools()
    {
        $toReturn = [];
        $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";
        $builder = $this->getEntityManager()->getRepository(User::class)->createQueryBuilder('u');
        $toReturn = $builder
            ->innerJoin('u.school', 's')
            ->where('s.testSchool = 1')
            ->andWhere($userTypeCondition)
            ->distinct()
            ->getQuery()
            ->getResult();

        return count($toReturn);
    }

    public function getCountTestSchoolsAuthority()
    {
        $toReturn = [];
        $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_AUTHORITY_LITE\"%'";
        $builder = $this->getEntityManager()->getRepository(User::class)->createQueryBuilder('u');
        $toReturn = $builder
            ->innerJoin('u.schoolAuthority', 'sa')
            ->where('sa.testSchoolAuthority = 1')
            ->andWhere($userTypeCondition)
            ->distinct()
            ->getQuery()
            ->getResult();

        return count($toReturn);
    }

    public function getTeachersRelated(int $schoolId)
    {

        return $this->createQueryBuilder('u')
            ->andWhere('u.school = :schoolId')
            ->andWhere('u.roles NOT LIKE :sLike')
            ->setParameter('schoolId', $schoolId)
            ->setParameter('sLike', '%SCHOOL%')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $schoolAuthorityId
     * @return array
     */
    public function getSchoolsRelated(int $schoolAuthorityId): array
    {
        $userTypeCondition = "u.roles LIKE '%\"ROLE_SCHOOL\"%' OR u.roles LIKE '%\"ROLE_SCHOOL_LITE\"%'";

        $users = $this->createQueryBuilder('u')
            ->innerJoin('u.school', 's')
            ->where($userTypeCondition)
            ->andWhere('s.schoolAuthority = :schoolAuthorityId')
            ->setParameter('schoolAuthorityId', $schoolAuthorityId)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        $schoolEM = $this->getEntityManager()->getRepository(School::class);
        $schools = $schoolEM->findBy(['schoolAuthority' => $schoolAuthorityId]);
        return [
            'users' => $users,
            'schools' => $schools,
        ];
    }


}



<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Service;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;
use function in_array;
use function is_null;
use function sort;
use const SORT_NUMERIC;

/**
 * Class QuestionnaireService
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle
 */
class QuestionnaireService
{

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * QuestionnaireService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $userId
     * @param int $questionnaireId
     * @return false|int
     */
    public function userFilledOutQuestionnaire(int $userId, int $questionnaireId)
    {

        $toReturn = false;
        $result = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Result::class, 'r')
            ->where('r.user = :uId')
            ->andWhere('r.questionnaire = :qId')
            ->setParameters(['uId' => $userId, 'qId' => $questionnaireId])
            ->orderBy('r.creationDate', 'DESC')
            ->getQuery()
            ->getScalarResult();

        if (count($result) > 0) {
            $toReturn = [
                'times' => count($result),
                'date' => $result[0]['r_creationDate']->format('d.m.Y H:i:s'),
            ];
        }

        return $toReturn;

    }

    public function getQuestionnaire(int $questionnaireId): ?Questionnaire
    {
        return $this->entityManager->getRepository(Questionnaire::class)->find($questionnaireId);
    }

    public function getQuestionnaireData(int $questionnaireId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('q, qg, quest, qc, cat')
            ->from(Questionnaire::class, 'q')
            ->leftJoin('q.category', 'cat')
            ->leftJoin('q.questionGroups', 'qg')
            ->leftJoin('qg.questions', 'quest')
            ->leftJoin('quest.choices', 'qc')
            ->where('q.id = :qId')
            ->setParameters(['qId' => $questionnaireId])
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws Exception
     */
    public function getQuestionnaireResultBySchool(Questionnaire $questionnaire, School $school, bool $forSchoolAuthorityOnly = false): array
    {
        $sql = "SELECT u.code, r.rating, ra.*, r.share_with_school_authority, r.share_notices, r.share
            FROM publicuserbundle_user u
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = :questionnaireId " . ($forSchoolAuthorityOnly ? ' AND share_with_school_authority = TRUE' : '') . "
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id
            WHERE u.school_id = :schoolId AND u.code IS NOT NULL
            GROUP BY ra.id;";

        $rawData = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'schoolId' => $school->getId(),
            'questionnaireId' => $questionnaire->getId()
        ]);
        $result = ['questions' => [], 'results' => []];
        foreach ($rawData as $data) {
            if (! in_array($data['result_id'], $result['results'])) {
                $result['results'][] = $data['result_id'];
            }
            if (! isset($result['questions'][$data['question_id']])) {
                $result['questions'][$data['question_id']] = ['answers' => [], 'skipped' => 0, 'ratings' => [], 'choices' => [], 'values' => []];
            }
            if ($data['skipped']) {
                $result['questions'][$data['question_id']]['skipped']++;
            }
            if ($data['choice_id']) {
                if (! isset($result['questions'][$data['question_id']]['choices'][$data['choice_id']])) {
                    $result['questions'][$data['question_id']]['choices'][$data['choice_id']] = 0;
                }
                $result['questions'][$data['question_id']]['choices'][$data['choice_id']]++;
            } elseif (! is_null($data['value'])) {
                if (! isset($result['questions'][$data['question_id']]['values'][$data['value']])) {
                    $result['questions'][$data['question_id']]['values'][$data['value']] = 0;
                }
                $result['questions'][$data['question_id']]['values'][$data['value']]++;
            }
            $result['questions'][$data['question_id']]['ratings'][] = $data['rating'];

            $result['questions'][$data['question_id']]['answers'][] = [
                'type' => $data['type'],
                'value' => $data['value'],
                'choice_id' => $data['choice_id'],
                'share_notices' => $data['share_notices'],
                'share' => $data['share'],
            ];
        }

        return $result;
    }


    /**
     * @throws Exception
     */
    public function getQuestionnaireResultBySchoolAuthority(Questionnaire $questionnaire, SchoolAuthority $schoolAuthority): array
    {
        $sql = <<<'SQL'
            SELECT u.code, r.rating, ra.*
            FROM publicuserbundle_user u
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = :questionnaireId 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id
                     inner join school s on u.school_id = s.id
            WHERE s.school_authority_id = :schoolAuthId AND u.code IS NOT NULL
            GROUP BY ra.id;
SQL;
        $rawData = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'schoolAuthId' => $schoolAuthority->getId(),
            'questionnaireId' => $questionnaire->getId()
        ]);
        $result = ['questions' => [], 'results' => []];
        foreach ($rawData as $data) {
            if (! in_array($data['result_id'], $result['results'])) {
                $result['results'][] = $data['result_id'];
            }
            if (! isset($result['questions'][$data['question_id']])) {
                $result['questions'][$data['question_id']] = ['answers' => [], 'skipped' => 0, 'ratings' => [], 'choices' => [], 'values' => []];
            }
            if ($data['skipped']) {
                $result['questions'][$data['question_id']]['skipped']++;
            }
            if ($data['choice_id']) {
                if (! isset($result['questions'][$data['question_id']]['choices'][$data['choice_id']])) {
                    $result['questions'][$data['question_id']]['choices'][$data['choice_id']] = 0;
                }
                $result['questions'][$data['question_id']]['choices'][$data['choice_id']]++;
            } elseif (! is_null($data['value'])) {
                if (! isset($result['questions'][$data['question_id']]['values'][$data['value']])) {
                    $result['questions'][$data['question_id']]['values'][$data['value']] = 0;
                }
                $result['questions'][$data['question_id']]['values'][$data['value']]++;
            }
            $result['questions'][$data['question_id']]['ratings'][] = $data['rating'];
            $result['questions'][$data['question_id']]['answers'][] = [
                'type' => $data['type'],
                'value' => $data['value'],
                'choice_id' => $data['choice_id']
            ];
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public function getSchoolIndex(): float
    {
        $sql = <<<'SQL'
            SELECT AVG(ra.value) as value
            FROM questionnairebundle_category c 
            LEFT JOIN publicuserbundle_user u on u.code IS NOT NULL
            LEFT JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            LEFT JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            LEFT JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            LEFT JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            LEFT JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE q.type = 'school'
            GROUP BY q.type;
SQL;
        $rawData = $this->entityManager->getConnection()->fetchAssociative($sql);
        return $rawData['value'];
    }


    /**
     * @throws Exception
     */
    public function getSchoolIndexForQuestionnaire(School $school, Questionnaire $questionnaire): float
    {
        $sql = <<<'SQL'
            SELECT AVG(ra.value) as value
            FROM questionnairebundle_category c 
            LEFT JOIN publicuserbundle_user u on u.code IS NOT NULL AND u.school_id = :schoolId
            LEFT JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            LEFT JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            LEFT JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            LEFT JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            LEFT JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE q.type = 'school' AND q.id = :questionnaireId
            GROUP BY q.type;
SQL;
        $rawData = $this->entityManager->getConnection()->fetchAssociative($sql, ['questionnaireId' => $questionnaire->getId(), 'schoolId' => $school->getId()]);
        if (is_null($rawData['value'])) {
            return 0.0;
        }
        return $rawData['value'];
    }

    /**
     * @param int    $questionnaireId
     * @param string $schoolType
     * @return array
     * @throws Exception
     */
    public function getSelfRatingValuesByQuestionnaireId(int $questionnaireId, bool $all = false, string $schoolType = 'weiterführende Schule'): array
    {
        $result = [];
        $sql = "
            SELECT AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL AND u.school_type = :schoolType" . ($all === false ? " AND u.school_id IS NOT NULL" : "") . "
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_question_has_school_type qqhst on qu.id = qqhst.question_id AND qqhst.school_type = :schoolType
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE q.type = 'school' AND q.id = :questionnaireId
            GROUP BY r.id;";
        $rawData = array_map('floatval', array_column($this->entityManager->getConnection()->fetchAllAssociative($sql, ['questionnaireId' => $questionnaireId, 'schoolType' => $schoolType]), 'value'));
        sort($rawData, SORT_NUMERIC);
        return $rawData;
    }

    /**
     * @link https://stackoverflow.com/a/29205714/7646716
     * @param array $array
     * @return int[]
     */
    function getBoxPlotValues(array $array): array
    {
        $return = [
            'lower_outlier' => 0,
            'min' => 0,
            'q1' => 0,
            'median' => 0,
            'q3' => 0,
            'max' => 0,
            'higher_outlier' => 0,
        ];

        $array_count = count($array);

        if ($array_count <= 0) {
            return $return;
        }

        sort($array, SORT_NUMERIC);
        $return['min'] = $array[0];
        $return['lower_outlier'] = [];
        $return['max'] = $array[$array_count - 1];
        $return['higher_outlier'] = [];
        $middle_index = floor($array_count / 2);
        $return['median'] = $array[$middle_index]; // Assume an odd # of items
        $lower_values = [];
        $higher_values = [];

        // If we have an even number of values, we need some special rules
        if ($array_count % 2 == 0) {
            // Handle the even case by averaging the middle 2 items
            $return['median'] = round(($return['median'] + $array[$middle_index - 1]) / 2);

            foreach ($array as $idx => $value) {
                if ($idx < ($middle_index - 1)) $lower_values[] = $value; // We need to remove both of the values we used for the median from the lower values
                elseif ($idx > $middle_index) $higher_values[] = $value;
            }
        } else {
            foreach ($array as $idx => $value) {
                if ($idx < $middle_index) $lower_values[] = $value;
                elseif ($idx > $middle_index) $higher_values[] = $value;
            }
        }

        $lower_values_count = count($lower_values);
        $lower_middle_index = floor($lower_values_count / 2);
        $return['q1'] = $lower_values[$lower_middle_index];
        if ($lower_values_count % 2 == 0)
            $return['q1'] = round(($return['q1'] + $lower_values[$lower_middle_index - 1]) / 2);

        $higher_values_count = count($higher_values);
        $higher_middle_index = floor($higher_values_count / 2);
        $return['q3'] = $higher_values[$higher_middle_index];
        if ($higher_values_count % 2 == 0)
            $return['q3'] = round(($return['q3'] + $higher_values[$higher_middle_index - 1]) / 2);

        // Check if min and max should be capped
        $iqr = $return['q3'] - $return['q1']; // Calculate the Inner Quartile Range (iqr)

        $return['min'] = $return['q1'] - 1.5 * $iqr; // This ( q1 - 1.5*IQR ) is actually the lower bound,
        // We must compare every value in the lower half to this.
        // Those less than the bound are outliers, whereas
        // The least one that greater than this bound is the 'min'
        // for the boxplot.
        foreach ($lower_values as $idx => $value) {
            if ($value < $return['min'])  // when values are less than the bound
            {
                $return['lower_outlier'][$idx] = $value; // keep the index here seems unnecessary
                // but those who are interested in which values are outliers
                // can take advantage of this and asort to identify the outliers
            } else {
                $return['min'] = $value; // when values that greater than the bound
                break;  // we should break the loop to keep the 'min' as the least that greater than the bound
            }
        }

        $return['max'] = $return['q3'] + 1.5 * $iqr; // This ( q3 + 1.5*IQR ) is the same as previous.
        foreach (array_reverse($higher_values) as $idx => $value) {
            if ($value > $return['max']) {
                $return['higher_outlier'][$idx] = $value;
            } else {
                $return['max'] = $value;
                break;
            }
        }
        return $return;
    }

    /**
     * @throws Exception
     */
    public function getChartData(): array
    {
        $sql = <<<'SQL'
            SELECT c.id as categoryId, c.name as categoryName, c.color as categoryColor, c.icon as categoryIcon, q.id as questionnaireId, q.name as questionnaireName, COUNT(DISTINCT r.id) as results, COUNT(ra.id) as answers, AVG(ra.value) as value
            FROM questionnairebundle_category c 
            LEFT JOIN publicuserbundle_user u on u.code IS NOT NULL
            LEFT JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            LEFT JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            LEFT JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            LEFT JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            LEFT JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE q.type = 'school'
            GROUP BY c.id, q.id
            ORDER BY c.position ASC, q.position ASC;
SQL;
        $rawData = $this->entityManager->getConnection()->fetchAllAssociative($sql);
        $chartData = [];
        foreach ($rawData as $data) {
            if (! isset($chartData[$data['categoryId']])) {
                $chartData[$data['categoryId']] = [
                    'id' => $data['categoryId'],
                    'name' => $data['categoryName'],
                    'icon' => $data['categoryIcon'],
                    'color' => $data['categoryColor'],
                    'questionnaires' => [],
                    'value' => null,
                    'schoolValue' => null
                ];
            }
            $chartData[$data['categoryId']]['questionnaires'][$data['questionnaireId']] = [
                'id' => $data['questionnaireId'],
                'name' => $data['questionnaireName'],
                'results' => $data['results'],
                'answers' => $data['answers'],
                'value' => $data['value'],
            ];
            if (! is_null($data['value'])) {
                if (is_null($chartData[$data['categoryId']]['value'])) {
                    $chartData[$data['categoryId']]['value'] = $data['value'];
                } else {
                    $chartData[$data['categoryId']]['value'] = ($chartData[$data['categoryId']]['value'] + $data['value']) / 2;
                }
            }
        }

        $sqlSchools = <<<'SQL'
            SELECT c.id as categoryId, c.name as categoryName, c.color as categoryColor, c.icon as categoryIcon, q.id as questionnaireId, q.name as questionnaireName, COUNT(DISTINCT r.id) as results, COUNT(ra.id) as answers, AVG(ra.value) as schoolValue
            FROM questionnairebundle_category c 
            LEFT JOIN publicuserbundle_user u on u.code IS NOT NULL AND u.school_type = "weiterführende Schule" AND u.school_id IS NOT NULL
            LEFT JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            LEFT JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            LEFT JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            LEFT JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            LEFT JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            WHERE q.type = 'school'
            GROUP BY c.id, q.id
            ORDER BY c.position ASC, q.position ASC;
SQL;
        $rawDataSchools = $this->entityManager->getConnection()->fetchAllAssociative($sqlSchools);
        foreach ($rawDataSchools as $data) {
            if (isset($chartData[$data['categoryId']])) {
                if (isset($chartData[$data['categoryId']]['questionnaires'][$data['questionnaireId']])) {
                    $chartData[$data['categoryId']]['questionnaires'][$data['questionnaireId']]['schoolValue'] = $data['schoolValue'];
                }
                if (! is_null($data['schoolValue'])) {
                    if (is_null($chartData[$data['categoryId']]['schoolValue'])) {
                        $chartData[$data['categoryId']]['schoolValue'] = $data['schoolValue'];
                    } else {
                        $chartData[$data['categoryId']]['schoolValue'] = ($chartData[$data['categoryId']]['schoolValue'] + $data['schoolValue']) / 2;
                    }
                }
            }
        }

        return $chartData;
    }


    public function getQuestionaireResultsByUser($user): array
    {
        $where = '';
        if (is_array($user)) {
            $where = ' where  u.id IN(' . implode(',', $user) . ') ';
        } elseif (is_numeric($user)) {
            $where = ' where  u.id=' . $user . ' ';
        }

        $sql = '
            SELECT c.id as categoryId,
                c.name as categoryName,
                c.color as categoryColor, 
                c.icon as categoryIcon, 
                q.id as questionnaireId, 
                q.name as questionnaireName,
                u.id as userId, 
                r.id as resultId,
                r.creation_date as creationDate,
                q.site_id as Site,
                qu.recommendation_id as recommendation,
                COUNT(DISTINCT r.id) as results, 
                COUNT(ra.id) as answers, 
                AVG(ra.value) as value
            FROM questionnairebundle_category c 
            INNER JOIN publicuserbundle_user u on u.code IS NOT NULL or school_authority_id IS NOT NULL
            INNER JOIN questionnairebundle_questionnaire q on c.id = q.category_id
            INNER JOIN questionnairebundle_question_group qg on q.id = qg.questionnaire_id and qg.position = 1
            INNER JOIN questionnairebundle_question qu on qg.id = qu.question_group_id
            INNER JOIN questionnairebundle_result r on r.id = (SELECT id 
                    FROM questionnairebundle_result 
                    WHERE user_id = u.id AND questionnaire_id = q.id 
                    ORDER BY creation_date DESC LIMIT 1)
            INNER JOIN questionnairebundle_result_answer ra on r.id = ra.result_id AND qu.id = ra.question_id
            ' . $where . '
            GROUP BY c.id, q.id
            ORDER BY c.position ASC, q.position ASC;';

        $rawData = $this->entityManager->getConnection()->fetchAllAssociative($sql);


        return $rawData;
    }


}
<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;
use Trollfjord\Entity\SchoolType;
use function array_keys;
use function array_sum;
use function count;
use function implode;
use function is_array;
use function is_null;
use function join;
use function str_replace;
use function strlen;
use function strpos;
use function substr;

/**
 * Class QuestionnaireExcelService
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle
 */
class QuestionnaireExcelService
{

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var QuestionnaireService
     */
    protected QuestionnaireService $questionnaireService;

    /**
     * @var string|null
     */
    protected ?string $currentCell = null;

    /**
     * QuestionnaireService constructor.
     * @param EntityManagerInterface $entityManager
     * @param QuestionnaireService   $questionnaireService
     */
    public function __construct(EntityManagerInterface $entityManager, QuestionnaireService $questionnaireService)
    {
        $this->entityManager = $entityManager;
        $this->questionnaireService = $questionnaireService;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSchoolExport4Questionnaire(School $school, Questionnaire $questionnaire): IWriter
    {
        $questionnaire->setCurrentSchoolType($school->getSchoolType());
        $result = $this->questionnaireService->getQuestionnaireResultBySchool($questionnaire, $school);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Auswertung');
        $row = 1;
        $sheet->setCellValue('A' . $row, $questionnaire->getName());
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(22);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $row++;
        $sheet->setCellValue('A' . $row, $school->getName() . ' aus ' . $school->getAddress()->getCity());
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $row++;
        $sheet->setCellValue('A' . $row, "Ausgefüllte Fragebögen:");
        $sheet->setCellValue('B' . $row, count($result['results']));
        $row += 2;

        foreach ($questionnaire->getQuestionGroups() as $questionGroup) {
            $sheet->mergeCells('A' . $row . ':P' . $row);
            $sheet->setCellValue('A' . $row, $questionGroup->getName());
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(18);
            $row++;
            $sheet->setCellValue('A' . $row, $questionGroup->getDescription());
            $sheet->mergeCells('A' . $row . ':P' . $row);
            $row++;

            foreach ($questionGroup->getQuestions() as $question) {
                $sheet->setCellValue('A' . $row, $question->getQuestion());
                $sheet->getRowDimension($row)->setRowHeight(-1);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                $row++;
                if ($question->getType() === 'multiple_choice') {
                    foreach ($question->getChoices() as $choice) {
                        $sheet->setCellValue('A' . $row, $choice->getChoice());
                        $choiceAnswered = 0;
                        if (isset($result['questions'][$question->getId()]) && isset($result['questions'][$question->getId()]['choices'][$choice->getId()])) {
                            $choiceAnswered = $result['questions'][$question->getId()]['choices'][$choice->getId()];
                        }
                        $sheet->setCellValue('B' . $row, $choiceAnswered);
                        $row++;
                    }
                    if ($question->getProperties()['allow_other_choice']) {
                        $sheet->setCellValue('A' . $row, 'Andere*');
                        $sheet->setCellValue('B' . $row, array_sum($result['questions'][$question->getId()]['values']));
                        $row++;
                        if (count($result['questions'][$question->getId()]['values']) > 0) {
                            $sheet->setCellValue('A' . $row, '*) ' . join(', ', array_keys($result['questions'][$question->getId()]['values'])));
                            $row++;
                        }
                    }
                } elseif ($question->getType() === 'opinion_scale') {
                    for ($i = 0; $i < $question->getProperties()['steps']; $i++) {
                        $sheet->setCellValue('A' . $row, $i);
                        $choiceAnswered = 0;
                        if (isset($result['questions'][$question->getId()]) && isset($result['questions'][$question->getId()]['values'][$i])) {
                            $choiceAnswered = $result['questions'][$question->getId()]['values'][$i];
                        }
                        $sheet->setCellValue('B' . $row, $choiceAnswered);
                        $row++;
                    }
                } elseif ($question->getType() === 'long_text') {
                    if (isset($result['questions'][$question->getId()])) {
                        foreach ($result['questions'][$question->getId()]['values'] as $answer => $count) {
                            $sheet->setCellValue('A' . $row, $answer);
                            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                            $sheet->setCellValue('B' . $row, $count);
                            $row++;
                        }
                    }
                }
                $row++;
            }
            $row++;
        }
        $sheet->getColumnDimension('A')->setWidth(40);
        // Create your Office 2007 Excel (XLSX Format)
        return new Xlsx($spreadsheet);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function getSchoolAuthoritySchoolsExportByCategory(SchoolAuthority $schoolAuthority, Category $category): IWriter
    {
        $spreadsheet = new Spreadsheet();
        $first = true;
        foreach ($category->getQuestionnaires() as $questionnaire) {
            if ($questionnaire->getType() !== 'school') {
                continue;
            } elseif (! $first) {
                $sheet = $spreadsheet->createSheet();
                $spreadsheet->setActiveSheetIndex($spreadsheet->getSheetCount() - 1);
            }
            $questionnaire->setCurrentSchoolType($this->entityManager->find(SchoolType::class, "weiterführende Schule"));
            $schoolResults = [];
            $resultsCount = 0;
            foreach ($schoolAuthority->getSchools() as $school) {
                $res = $this->questionnaireService->getQuestionnaireResultBySchool($questionnaire, $school, true);
                $schoolResults[] = [
                    'result' => $res,
                    'school' => $school
                ];
                $resultsCount += count($res['results']);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($questionnaire->getName(), 0, 31));
            $row = 1;
            $sheet->setCellValue('A' . $row, $questionnaire->getName());
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(22);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $row++;
            $sheet->setCellValue('A' . $row, $schoolAuthority->getName() . ' aus ' . $schoolAuthority->getAddress()->getCity());
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $row++;
            $sheet->setCellValue('A' . $row, "Ausgefüllte Fragebögen:");
            $sheet->setCellValue('B' . $row, $resultsCount);
            $row++;
            $sheet->setCellValue('A' . $row, "Schulen teilgenommen:");
            $sheet->setCellValue('B' . $row, count($schoolResults));
            $row += 2;

            foreach ($questionnaire->getQuestionGroups() as $questionGroup) {
                $sheet->mergeCells('A' . $row . ':P' . $row);
                $sheet->setCellValue('A' . $row, $questionGroup->getName());
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(18);
                $row++;
                $sheet->setCellValue('A' . $row, $questionGroup->getDescription());
                $sheet->mergeCells('A' . $row . ':P' . $row);
                $row++;

                foreach ($questionGroup->getQuestions() as $question) {
                    $sheet->setCellValue('A' . $row, $question->getQuestion() . ($question->getType() === 'multiple_choice' && $question->getProperties()['allow_multiple_selection'] ? ' (Mehrfachauswahl)' : ''));
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                    $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                    if ($question->getType() === 'multiple_choice') {
                        $this->getNextCell(true);
                        foreach ($schoolResults as $schoolResult) {
                            $sheet->setCellValue($this->getNextCell() . $row, $schoolResult['school']->getName() . ' (Fragebögen: ' . count($schoolResult['result']['results']) . ')');
                        }
                        $row++;
                        foreach ($question->getChoices() as $choice) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, $choice->getChoice());
                            $sheet->getStyle($cell . $row)->getAlignment()->setWrapText(true);
                            foreach ($schoolResults as $schoolResult) {
                                $choiceAnswered = 0;
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && isset($schoolResult['result']['questions'][$question->getId()]['choices'][$choice->getId()])) {
                                    $choiceAnswered = $schoolResult['result']['questions'][$question->getId()]['choices'][$choice->getId()];
                                }
                                $cell = $this->getNextCell();
                                $sheet->setCellValue($cell . $row, $choiceAnswered);
                            }
                            $row++;
                        }
                        if ($question->getProperties()['allow_other_choice']) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, 'Andere*');
                            foreach ($schoolResults as $schoolResult) {
                                $cell = $this->getNextCell();
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && count($schoolResult['result']['questions'][$question->getId()]['values'])) {
                                    $sheet->setCellValue($cell . $row, array_sum($schoolResult['result']['questions'][$question->getId()]['values']));
                                    $row++;
                                    $sheet->setCellValue($cell . $row, '*) ' . join(', ', array_keys($schoolResult['result']['questions'][$question->getId()]['values'])));
                                    $row--;
                                } else {
                                    $sheet->setCellValue($cell . $row, 0);
                                }
                            }
                            $row += 2;
                        }
                    } elseif ($question->getType() === 'opinion_scale') {
                        $this->getNextCell(true);
                        foreach ($schoolResults as $schoolResult) {
                            $sheet->setCellValue($this->getNextCell() . $row, $schoolResult['school']->getName() . ' (Fragebögen: ' . count($schoolResult['result']['results']) . ')');
                        }
                        $row++;
                        for ($i = 0; $i < $question->getProperties()['steps']; $i++) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, $i);
                            foreach ($schoolResults as $schoolResult) {
                                $choiceAnswered = 0;
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && isset($schoolResult['result']['questions'][$question->getId()]['values'][$i])) {
                                    $choiceAnswered = $schoolResult['result']['questions'][$question->getId()]['values'][$i];
                                }
                                $cell = $this->getNextCell();
                                $sheet->setCellValue($cell . $row, $choiceAnswered);
                            }
                            $row++;
                        }
                    } elseif ($question->getType() === 'long_text') {
                        $cell = $this->getNextCell(true);
                        foreach ($schoolResults as $schoolResult) {
                            $oldRow = $row;
                            $cell = $this->getNextCell();
                            $sheet->setCellValue($cell . $row, $schoolResult['school']->getName() . ' (Fragebögen: ' . count($schoolResult['result']['results']) . ')');
                            $row++;
                            if (isset($schoolResult['result']['questions'][$question->getId()])) {
                                foreach ($schoolResult['result']['questions'][$question->getId()]['values'] as $answer => $count) {
                                    $sheet->setCellValue($cell . $row, $answer . ' (' . $count . 'x)');
                                    $sheet->getStyle($cell . $row)->getAlignment()->setWrapText(true);
                                    $row++;
                                }
                            }
                            $row = $oldRow;
                        }
                    }
                    $row++;
                }
                $row++;
            }
            $sheet->getColumnDimension('A')->setWidth(40);
            $first = false;
        }
        return new Xlsx($spreadsheet);
    }


    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function getAllSchoolsExportByCategory(Category $category, string $schoolType = 'all'): IWriter
    {
        $spreadsheet = new Spreadsheet();
        $first = true;
        foreach ($category->getQuestionnaires() as $questionnaire) {
            if ($questionnaire->getType() !== 'school') {
                continue;
            } elseif (! $first) {
                $sheet = $spreadsheet->createSheet();
                $spreadsheet->setActiveSheetIndex($spreadsheet->getSheetCount() - 1);
            }
            $questionnaire->setCurrentSchoolType($this->entityManager->find(SchoolType::class, "weiterführende Schule"));
            $schoolResults = [];
            $resultsCount = 0;
            /** @var School $school */
            foreach ($this->entityManager->getRepository(School::class)->findBy(['testSchool' => false]) as $school) {
                $useSchool = true;
                if ($schoolType === 'not_mint') {
                    foreach ($school->getTags() as $tag) {
                        if ($tag->getName() == "MINT-EC" ||
                            $tag->getName() == "MINT Zukunft" ||
                            $tag->getName() == "JIA") {
                            $useSchool = false;
                        }
                    }
                } elseif ($schoolType === 'mint') {
                    $useSchool = false;
                    foreach ($school->getTags() as $tag) {
                        if ($tag->getName() == "MINT-EC" ||
                            $tag->getName() == "MINT Zukunft" ||
                            $tag->getName() == "JIA") {
                            $useSchool = true;
                        }
                    }
                }
                if ($useSchool) {
                    $res = $this->questionnaireService->getQuestionnaireResultBySchool($questionnaire, $school, false);
                    if (count($res['results']) > 0) {
                        $schoolResults[] = [
                            'result' => $res,
                            'school' => $school
                        ];
                        $resultsCount += count($res['results']);
                    }
                }
            }
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($questionnaire->getName(), 0, 31));
            $row = 1;
            $sheet->setCellValue('A' . $row, $questionnaire->getName());
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(22);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $row++;
            $sheet->setCellValue('A' . $row, "Ausgefüllte Fragebögen:");
            $sheet->setCellValue('B' . $row, $resultsCount);
            $row++;
            $sheet->setCellValue('A' . $row, "Schulen teilgenommen:");
            $sheet->setCellValue('B' . $row, count($schoolResults));
            $row += 2;

            foreach ($questionnaire->getQuestionGroups() as $questionGroup) {
                $sheet->mergeCells('A' . $row . ':P' . $row);
                $sheet->setCellValue('A' . $row, $questionGroup->getName());
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(18);
                $row++;
                $sheet->setCellValue('A' . $row, $questionGroup->getDescription());
                $sheet->mergeCells('A' . $row . ':P' . $row);
                $row++;

                foreach ($questionGroup->getQuestions() as $question) {
                    $sheet->setCellValue('A' . $row, $question->getQuestion() . ($question->getType() === 'multiple_choice' && $question->getProperties()['allow_multiple_selection'] ? ' (Mehrfachauswahl)' : ''));
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                    $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                    if ($question->getType() === 'multiple_choice') {
                        $this->getNextCell(true);
                        foreach ($schoolResults as $schoolResult) {
                            $sheet->setCellValue($this->getNextCell() . $row, $schoolResult['school']->getId()/* . ' (Fragebögen: ' . count($schoolResult['result']['results']) . ')'*/);
                        }
                        $row++;
                        foreach ($question->getChoices() as $choice) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, $choice->getChoice());
                            $sheet->getStyle($cell . $row)->getAlignment()->setWrapText(true);
                            foreach ($schoolResults as $schoolResult) {
                                $choiceAnswered = 0;
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && isset($schoolResult['result']['questions'][$question->getId()]['choices'][$choice->getId()])) {
                                    $choiceAnswered = $schoolResult['result']['questions'][$question->getId()]['choices'][$choice->getId()];
                                }
                                $cell = $this->getNextCell();
                                $sheet->setCellValue($cell . $row, $choiceAnswered);
                            }
                            $row++;
                        }
                        if ($question->getProperties()['allow_other_choice']) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, 'Andere*');
                            foreach ($schoolResults as $schoolResult) {
                                $cell = $this->getNextCell();
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && count($schoolResult['result']['questions'][$question->getId()]['values'])) {
                                    $sheet->setCellValue($cell . $row, array_sum($schoolResult['result']['questions'][$question->getId()]['values']));
                                    $row++;
                                    $sheet->setCellValue($cell . $row, '*) ' . join(', ', array_keys($schoolResult['result']['questions'][$question->getId()]['values'])));
                                    $row--;
                                } else {
                                    $sheet->setCellValue($cell . $row, 0);
                                }
                            }
                            $row += 2;
                        }
                    } elseif ($question->getType() === 'opinion_scale') {
                        $this->getNextCell(true);
                        foreach ($schoolResults as $schoolResult) {
                            $sheet->setCellValue($this->getNextCell() . $row, $schoolResult['school']->getId()/* . ' (Fragebögen: ' . count($schoolResult['result']['results']) . ')'*/);
                        }
                        $row++;
                        $cellsForSum = [];
                        for ($i = 0; $i < $question->getProperties()['steps']; $i++) {
                            $cell = $this->getNextCell(true);
                            $cellsForSum[$cell] = [];
                            $sheet->setCellValue($cell . $row, $i);
                            foreach ($schoolResults as $schoolResult) {
                                $choiceAnswered = 0;
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && isset($schoolResult['result']['questions'][$question->getId()]['values'][$i])) {
                                    $choiceAnswered = $schoolResult['result']['questions'][$question->getId()]['values'][$i];
                                }
                                $cell = $this->getNextCell();
                                $sheet->setCellValue($cell . $row, $choiceAnswered);
                                $cellsForSum[$cell][] = $cell . $row;
                            }
                            $row++;
                        }
                        $sheet->setCellValue('A' . $row, 'Summe');
                        foreach ($cellsForSum as $cell => $values) {
                            if (count($values) > 0) {
                                $sheet->setCellValue($cell . $row, '=SUM(' . $values[0] . ':' . $values[count($values) - 1] . ')');
                            }
                        }
                        $row++;
                        $sheet->setCellValue('A' . $row, 'Avg');
                        foreach ($cellsForSum as $cell => $values) {
                            if (count($values) > 0) {
                                $formula = [];
                                foreach ($values as $cellV) {
                                    $formula[] = str_replace($cell, 'A', $cellV) . '*' . $cellV;
                                }
                                $formula = '((' . implode(')+(', $formula) . '))/' . $cell . ($row - 1);
                                $sheet->setCellValue($cell . $row, '=' . $formula);
                            }
                        }
                        $row++;
                    } elseif ($question->getType() === 'long_text') {
                        $cell = $this->getNextCell(true);
                        foreach ($schoolResults as $schoolResult) {
                            $oldRow = $row;
                            $cell = $this->getNextCell();
                            $sheet->setCellValue($cell . $row, $schoolResult['school']->getId()/* . ' (Fragebögen: ' . count($schoolResult['result']['results']) . ')'*/);
                            $row++;
                            if (isset($schoolResult['result']['questions'][$question->getId()])) {
                                foreach ($schoolResult['result']['questions'][$question->getId()]['values'] as $answer => $count) {
                                    $sheet->setCellValue($cell . $row, $answer . ' (' . $count . 'x)');
                                    $sheet->getStyle($cell . $row)->getAlignment()->setWrapText(true);
                                    $row++;
                                }
                            }
                            $row = $oldRow;
                        }
                    }
                    $row++;
                }
                $row++;
            }
            $sheet->getColumnDimension('A')->setWidth(40);
            $first = false;
        }
        return new Xlsx($spreadsheet);
    }

    /**
     * @param Category $category
     * @param bool     $onlyGermanSchools
     * @return IWriter
     * @throws \Doctrine\DBAL\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function getExportByCategory(Category $category, bool $onlyGermanSchools = false): IWriter
    {
        $spreadsheet = new Spreadsheet();
        $first = true;
        foreach ($category->getQuestionnaires() as $questionnaire) {
            if ($questionnaire->getType() !== 'school') {
                continue;
            } elseif (! $first) {
                $sheet = $spreadsheet->createSheet();
                $spreadsheet->setActiveSheetIndex($spreadsheet->getSheetCount() - 1);
            }
            $questionnaire->setCurrentSchoolType($this->entityManager->find(SchoolType::class, "weiterführende Schule"));

            $schoolResults = [];
            $resultsCount = 0;
            /** @var School $school */
            foreach ($this->entityManager->getRepository(School::class)->findAll() as $school) {
                if ($onlyGermanSchools && $school->getAddress() && ! is_null($school->getAddress()->getCountry())) {
                    continue;
                }
                $res = $this->questionnaireService->getQuestionnaireResultBySchool($questionnaire, $school);
                $schoolResults[] = [
                    'result' => $res,
                    'school' => $school
                ];
                $resultsCount += count($res['results']);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($questionnaire->getName(), 0, 31));
            $row = 1;
            $sheet->setCellValue('A' . $row, $questionnaire->getName());
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(22);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $row++;
            $sheet->setCellValue('A' . $row, "Ausgefüllte Fragebögen:");
            $sheet->setCellValue('B' . $row, $resultsCount);
            $row++;
            $sheet->setCellValue('A' . $row, "Schulen teilgenommen:");
            $sheet->setCellValue('B' . $row, count($schoolResults));
            $row++;
            $sheet->setCellValue('A' . $row, "Index Selbsteinschätzung:");
            $row += 2;

            $indexCells = [];
            foreach ($questionnaire->getQuestionGroups() as $questionGroup) {
                $sheet->mergeCells('A' . $row . ':P' . $row);
                $sheet->setCellValue('A' . $row, $questionGroup->getName());
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(18);
                $row++;
                $sheet->setCellValue('A' . $row, $questionGroup->getDescription());
                $sheet->mergeCells('A' . $row . ':P' . $row);
                $row++;

                foreach ($questionGroup->getQuestions() as $question) {
                    $sheet->setCellValue('A' . $row, $question->getQuestion() . ($question->getType() === 'multiple_choice' && $question->getProperties()['allow_multiple_selection'] ? ' (Mehrfachauswahl)' : ''));
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                    $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                    if ($question->getType() === 'multiple_choice') {
                        $this->getNextCell(true);
                        $row++;
                        foreach ($question->getChoices() as $choice) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, $choice->getChoice());
                            $sheet->getStyle($cell . $row)->getAlignment()->setWrapText(true);
                            $choiceAnswered = 0;
                            foreach ($schoolResults as $schoolResult) {
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && isset($schoolResult['result']['questions'][$question->getId()]['choices'][$choice->getId()])) {
                                    $choiceAnswered += $schoolResult['result']['questions'][$question->getId()]['choices'][$choice->getId()];
                                }
                            }
                            $cell = $this->getNextCell();
                            $sheet->setCellValue($cell . $row, $choiceAnswered);
                            $row++;
                        }
                        if ($question->getProperties()['allow_other_choice']) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, 'Andere*');
                            $choiceAnswered = 0;
                            $answers = [];
                            foreach ($schoolResults as $schoolResult) {
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && count($schoolResult['result']['questions'][$question->getId()]['values'])) {
                                    $choiceAnswered += array_sum($schoolResult['result']['questions'][$question->getId()]['values']);
                                    $answers = array_merge($answers, array_keys($schoolResult['result']['questions'][$question->getId()]['values']));
                                }
                            }
                            $cell = $this->getNextCell();
                            $sheet->setCellValue($cell . $row, $choiceAnswered);
                            $cell = $this->getNextCell();
                            $sheet->setCellValue($cell . $row, join(', ', $answers));
                            $row += 2;
                        }
                    } elseif ($question->getType() === 'opinion_scale') {
                        $this->getNextCell(true);
                        $row++;
                        $formula = "(";
                        $startRow = $row;
                        for ($i = 0; $i < $question->getProperties()['steps']; $i++) {
                            $cell = $this->getNextCell(true);
                            $sheet->setCellValue($cell . $row, $i);
                            $formula .= $cell . $row . "*";
                            $choiceAnswered = 0;
                            foreach ($schoolResults as $schoolResult) {
                                if (isset($schoolResult['result']['questions'][$question->getId()]) && isset($schoolResult['result']['questions'][$question->getId()]['values'][$i])) {
                                    $choiceAnswered += $schoolResult['result']['questions'][$question->getId()]['values'][$i];
                                }
                            }
                            $cell = $this->getNextCell();
                            $sheet->setCellValue($cell . $row, $choiceAnswered);
                            $formula .= $cell . $row . "+";
                            $row++;
                        }
                        $formula = substr($formula, 0, -1) . ") / SUM(B" . $startRow . ":B" . ($row - 1) . ")";
                        $sheet->setCellValue($cell . $row, "=" . $formula);
                        if ($questionGroup->getPosition() === 1) {
                            $indexCells[] = $cell . $row;
                        }
                        $row++;
                    } elseif ($question->getType() === 'long_text') {
                        $cell = $this->getNextCell(true);
                        $cell = $this->getNextCell();
                        $row++;
                        foreach ($schoolResults as $schoolResult) {
                            if (isset($schoolResult['result']['questions'][$question->getId()])) {
                                foreach ($schoolResult['result']['questions'][$question->getId()]['values'] as $answer => $count) {
                                    $sheet->setCellValue($cell . $row, trim($answer) . ' (' . $count . 'x)');
                                    $sheet->getStyle($cell . $row)->getAlignment()->setWrapText(true);
                                    $sheet->mergeCells('B' . $row . ':P' . $row);
                                    $row++;
                                }
                            }
                        }
                    }
                    $row++;
                }
                $row++;
            }
            $sheet->getColumnDimension('A')->setWidth(40);
            if (count($indexCells) > 0) {
                $sheet->setCellValue('B4', "=AVERAGE(" . join(",", $indexCells) . ")");
            }
            $first = false;
        }
        return new Xlsx($spreadsheet);
    }

    /**
     * @return IWriter
     * @throws Exception
     */
    public function getSchoolExcelExport(): IWriter
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;
        $sheet->setCellValue($this->getNextCell(true) . $row, 'Id');
        $sheet->setCellValue($this->getNextCell() . $row, 'Schulname');
        $sheet->setCellValue($this->getNextCell() . $row, 'Typ');
        $sheet->setCellValue($this->getNextCell() . $row, 'Aktiviert');
        $sheet->setCellValue($this->getNextCell() . $row, 'Tags');
        $sheet->setCellValue($this->getNextCell() . $row, 'Fragebögen (keine doppelten Lehrkraft/Fragebogen)');
        $sheet->setCellValue($this->getNextCell() . $row, 'Fragebögen (alle)');
        $sheet->setCellValue($this->getNextCell() . $row, 'Lehrkräfte');
        $row++;
        /** @var School $school */
        foreach ($this->entityManager->getRepository(School::class)->findBy(['testSchool' => false], ['name' => 'ASC']) as $school) {
            $sheet->setCellValue($this->getNextCell(true) . $row, $school->getId());
            $sheet->setCellValue($this->getNextCell() . $row, $school->getName());
            $sheet->setCellValue($this->getNextCell() . $row, $school->getSchoolType()->getName());
            $sheet->setCellValue($this->getNextCell() . $row, $school->isConfirmed() ? 'Ja' : 'Nein');
            $tags = [];
            foreach ($school->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
            $sheet->setCellValue($this->getNextCell() . $row, implode(', ', $tags));
            $teacher = 0;
            $resultsOnlyOnePerUserAndQuestionnaire = 0;
            $allResults = 0;
            foreach ($school->getUsers() as $user) {
                if ($user->getUsername() === null) {
                    $teacher++;
                    $formsByQuestionnaire = [];
                    $results = $this->entityManager->getRepository(Result::class)->findBy(['user' => $user]);
                    foreach ($results as $result) {
                        if (isset($formsByQuestionnaire[$user->getId() . '_' . $result->getQuestionnaire()->getId()])) {
                            $formsByQuestionnaire[$user->getId() . '_' . $result->getQuestionnaire()->getId()]++;
                        } else {
                            $resultsOnlyOnePerUserAndQuestionnaire++;
                            $formsByQuestionnaire[$user->getId() . '_' . $result->getQuestionnaire()->getId()] = 1;
                        }
                        $allResults++;
                    }
                }
            }
            $sheet->setCellValue($this->getNextCell() . $row, $resultsOnlyOnePerUserAndQuestionnaire);
            $sheet->setCellValue($this->getNextCell() . $row, $allResults);
            $sheet->setCellValue($this->getNextCell() . $row, $teacher);

            $row++;
        }
        $sheet->getColumnDimension('A')->setAutoSize(TRUE);
        $sheet->getColumnDimension('B')->setAutoSize(TRUE);
        // Create your Office 2007 Excel (XLSX Format)
        return new Xlsx($spreadsheet);
    }

    /**
     * @return IWriter
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function getResultExport(): IWriter
    {
        $spreadsheet = new Spreadsheet();
        $first = true;
        foreach ($this->entityManager->getRepository(Category::class)->findAll() as $category) {
            if (! $first) {
                $sheet = $spreadsheet->createSheet();
                $spreadsheet->setActiveSheetIndex($spreadsheet->getSheetCount() - 1);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($category->getName(), 0, 31));
            $row = 1;
            $sheet->setCellValue('A' . $row, $category->getName());
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(22);
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $row++;
            $sheet->setCellValue('A' . $row, "Diese Statistik enthält nur Werte von weiterführenden Schulen.");

            $cell = $this->getNextCell(true);
            foreach ($category->getQuestionnaires() as $questionnaire) {
                if ($questionnaire->getType() !== 'school') {
                    continue;
                }
                $row = 3;
                $sheet->setCellValue($cell . $row, $questionnaire->getName());
                $sheet->getStyle($cell . $row)->getFont()->setBold(true)->setSize(16);
                $sheet->getColumnDimension($cell)->setAutoSize(true);

                $oldCell = $cell;
                $row = 4;
                $selfRatingValues = $this->questionnaireService->getSelfRatingValuesByQuestionnaireId($questionnaire->getId());
                foreach ($this->questionnaireService->getBoxPlotValues($selfRatingValues) as $label => $boxPlotValue) {
                    $sheet->setCellValue($cell . $row, $label);
                    $sheet->getStyle($cell . $row)->getFont()->setBold(true);
                    $cell = $this->getNextCell();
                    $sheet->setCellValue($cell . $row, is_array($boxPlotValue) ? implode(", ", $boxPlotValue) : $boxPlotValue);
                    $sheet->getColumnDimension($cell)->setAutoSize(true);
                    $row++;
                    $cell = $this->currentCell = $oldCell;
                }
                $row += 2;
                $sheet->setCellValue($cell . $row, "Genutzte Werte:");
                $sheet->getStyle($cell . $row)->getFont()->setBold(true);
                $cell = $this->getNextCell();
                foreach ($selfRatingValues as $ratingValue) {
                    $sheet->setCellValue($cell . $row, $ratingValue);
                    $row++;
                }
                $cell = $this->getNextCell();
            }

            $first = false;
        }

        $spreadsheet->setActiveSheetIndex(0);
        // Create your Office 2007 Excel (XLSX Format)
        return new Xlsx($spreadsheet);
    }

    /**
     * @param bool $all
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getResult4Bloxplot(bool $all = false): array
    {
        $result = [];

        foreach ($this->entityManager->getRepository(Category::class)->findAll() as $category) {
            $result[] = ['name' => $category->getName(), "questionnaires" => []];
            foreach ($category->getQuestionnaires() as $questionnaire) {
                if ($questionnaire->getType() !== 'school') {
                    continue;
                }
                $selfRatingValues = $this->questionnaireService->getSelfRatingValuesByQuestionnaireId($questionnaire->getId(), $all);
                $questionnaireResult = ["name" => $questionnaire->getName(), "values" => $selfRatingValues];
                $result[count($result) - 1]["questionnaires"][] = $questionnaireResult;
            }
        }
        return $result;
    }

    /**
     * @param bool $reset
     * @param int  $steps
     * @return null|string
     * @throws Exception
     */
    protected function getNextCell(bool $reset = false, int $steps = 1): string
    {
        if ($reset) {
            $this->currentCell = null;
        }

        for ($i = 0; $i < $steps; $i++) {
            $this->currentCell = $this->_getNextCell($this->currentCell);
        }
        return $this->currentCell;
    }

    /**
     * @param string|null $cell
     * @return string
     * @throws Exception
     */
    protected function _getNextCell(string $cell = null): string
    {
        if (is_null($cell)) {
            $cell = 'A';
        } else {
            $cells = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            if (strlen($cell) == 1) {
                $newPos = strpos($cells, $cell) + 1;
                if ($newPos >= strlen($cells)) {
                    $cell = $cells[0] . $cells[0];
                } else {
                    $cell = $cells[$newPos];
                }
            } elseif (strlen($cell) == 2) {
                $newPos = strpos($cells, $cell[1]) + 1;
                if ($newPos >= strlen($cells)) {
                    $newPos = strpos($cells, $cell[0]) + 1;
                    if ($newPos >= strlen($cells)) {
                        throw new Exception('Cell is over ZZ');
                    }
                    $cell = $cells[$newPos] . $cells[0];
                } else {
                    $cell = $cell[0] . $cells[$newPos];
                }
            } else {
                throw new Exception('Cell is over ZZ');
            }
        }
        return $cell;
    }

}
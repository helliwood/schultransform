<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionChoice;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionGroup;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Result;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\ResultAnswer;

/**
 * Class TypeFormService
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle
 */
class TypeFormService
{

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;
    /**
     * @var HttpClientInterface
     */
    protected HttpClientInterface $client;

    /**
     * TypeFormService constructor.
     * @param EntityManagerInterface $entityManager
     * @param HttpClientInterface    $client
     */
    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $client)
    {
        $this->entityManager = $entityManager;
        $this->client = $client;
    }

    /**
     * @param string $formId
     * @return Questionnaire
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function createOrUpdateForm(string $formId): Questionnaire
    {
        $this->entityManager->beginTransaction();
        try {
            $response = $this->client->request('GET', 'https://api.typeform.com/forms/' . $formId);
            $formData = json_decode($response->getContent());
            $response = $this->client->request('GET', $formData->workspace->href, [
                'headers' => [
                    'Authorization' => 'Bearer 6kYRMY34vmAFHHoqfMMUEZtp1t5dg7wmP1YTZ7ec1a9a',
                ]
            ]);
            $workspaceData = json_decode($response->getContent());
            // Schulfrage?
            if (property_exists($formData, "welcome_screens")) {
                $category = $this->findOrCreateCategory($workspaceData->id, $workspaceData->name, $formData->welcome_screens[0]->title);
                $questionnaire = $this->findOrCreateQuestionnaire($formData->id, $category, $formData->title, $formData->welcome_screens[0]->properties->description, Questionnaire::TYPE_SCHOOL);
            } else { // Schulträger
                $category = $this->findOrCreateCategory($workspaceData->id, $workspaceData->name, $workspaceData->name);
                $questionnaire = $this->findOrCreateQuestionnaire($formData->id, $category, $formData->title, $formData->title, Questionnaire::TYPE_SCHOOL_BOARD);
            }
            foreach ($formData->fields as $k => $group) {
                if ($group->type === 'group' && ($k < 3 || $questionnaire->getType() === Questionnaire::TYPE_SCHOOL_BOARD)) {
                    $questionGroup = $this->findOrCreateQuestionGroup($group->id, $group->title, property_exists($group->properties, "description") ? $group->properties->description : null, $questionnaire);
                    foreach ($group->properties->fields as $q) {
                        $hasTitleInDescription = preg_match('/\*(.*)\*[\s]*(.*)$/ism', $q->title, $matches);
                        if ($hasTitleInDescription) {
                            $question = $this->findOrCreateQuestion($q->id, $matches[1], $matches[2], $q->type, $q->validations->required, json_decode(json_encode($q->properties), true), $questionGroup);
                        } else {
                            $question = $this->findOrCreateQuestion($q->id, null, $q->title, $q->type, $q->validations->required, json_decode(json_encode($q->properties), true), $questionGroup);
                        }
                        if (property_exists($q->properties, 'choices')) {
                            foreach ($q->properties->choices as $c) {
                                $choice = $this->findOrCreateQuestionChoice($c->id, $c->label, $question);
                            }
                        }
                    }
                }
            }
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        return $questionnaire;
    }

    /**
     * @param array $result
     * @return Result
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function saveResult(array $result): Result
    {
        $resultEntity = $this->findOrCreateResult($result);
        foreach ($result['form_response']['answers'] as $answer) {
            /** @var Question $question */
            $question = $this->entityManager->getRepository(Question::class)->findOneBy(['typeFormId' => $answer['field']['id']]);
            if (! is_null($question)) {
                switch ($answer['type']) {
                    case "choice":
                        foreach ($question->getChoices() as $choice) {
                            if ($choice->getChoice() == $answer['choice']['label']) {
                                $answerEntity = new ResultAnswer();
                                $answerEntity->setType($answer['type']);
                                $answerEntity->setResult($resultEntity);
                                $answerEntity->setQuestion($question);
                                $answerEntity->setChoice($choice);
                                $this->entityManager->persist($answerEntity);
                                $resultEntity->getAnswers()->add($answerEntity);
                            }
                        }
                        break;
                    case "choices":
                        foreach ($question->getChoices() as $choice) {
                            if (in_array($choice->getChoice(), $answer[$answer['type']]['labels'])) {
                                $answerEntity = new ResultAnswer();
                                $answerEntity->setType($answer['type']);
                                $answerEntity->setResult($resultEntity);
                                $answerEntity->setQuestion($question);
                                $answerEntity->setChoice($choice);
                                $this->entityManager->persist($answerEntity);
                                $resultEntity->getAnswers()->add($answerEntity);
                            }
                        }
                        if (isset($answer[$answer['type']]["other"])) {
                            $answerEntity = new ResultAnswer();
                            $answerEntity->setType($answer['type'] . "_other");
                            $answerEntity->setValue($answer[$answer['type']]["other"]);
                            $answerEntity->setResult($resultEntity);
                            $answerEntity->setQuestion($question);
                            $this->entityManager->persist($answerEntity);
                            $resultEntity->getAnswers()->add($answerEntity);
                        }
                        break;
                    default:
                        $answerEntity = new ResultAnswer();
                        $answerEntity->setType($answer['type']);
                        $answerEntity->setValue($answer[$answer['type']]);
                        $answerEntity->setResult($resultEntity);
                        $answerEntity->setQuestion($question);
                        $this->entityManager->persist($answerEntity);
                        $resultEntity->getAnswers()->add($answerEntity);
                }
            }
        }
        $this->entityManager->flush();
        return $resultEntity;
    }

    /**
     * @param array $result
     * @return object|Result
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function findOrCreateResult(array $result)
    {
        $resultEntity = $this->entityManager->getRepository(Result::class)->findOneBy(['typeFormId' => $result['event_id']]);
        if (is_null($resultEntity)) {
            $resultEntity = new Result();
            $resultEntity->setTypeFormId($result['event_id']);
            $userEntity = $this->entityManager->getRepository(User::class)->findOneBy(['code' => $result['form_response']['hidden']['usertoken']]);
            $resultEntity->setUser($userEntity);
            $questionnaireEntity = $this->entityManager->getRepository(Questionnaire::class)->findOneBy(['typeFormId' => $result['form_response']['form_id']]);
            if (is_null($questionnaireEntity)) {
                $questionnaireEntity = $this->createOrUpdateForm($result['form_response']['form_id']);
            }
            $resultEntity->setQuestionnaire($questionnaireEntity);
        }
        $resultEntity->setLandedAt(new DateTime($result['form_response']['landed_at']));
        $resultEntity->setSubmittedAt(new DateTime($result['form_response']['submitted_at']));
        $resultEntity->getAnswers()->clear();
        $this->entityManager->persist($resultEntity);
        $this->entityManager->flush();
        return $resultEntity;
    }

    /**
     * @param string $typeFormId
     * @param string $name
     * @param string $fullName
     * @return Category
     */
    public function findOrCreateCategory(string $typeFormId, string $name, string $fullName): Category
    {
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['typeFormId' => $typeFormId]);
        if (is_null($category)) {
            $categories = $this->entityManager->getRepository(Category::class)->findAll();
            $category = new Category();
            $category->setTypeFormId($typeFormId);
            $category->setPosition(count($categories) + 1);
        }
        $category->setName($name);
        $category->setFullName($fullName);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        return $category;
    }

    /**
     * @param string   $typeFormId
     * @param Category $category
     * @param string   $name
     * @param string   $fullName
     * @param string   $type
     * @return Questionnaire
     */
    public function findOrCreateQuestionnaire(string $typeFormId, Category $category, string $name, string $fullName, string $type): Questionnaire
    {
        $questionnaire = $this->entityManager->getRepository(Questionnaire::class)->findOneBy(['typeFormId' => $typeFormId]);
        if (is_null($questionnaire)) {
            $questionnaires = $this->entityManager->getRepository(Questionnaire::class)->findBy(['category' => $category]);
            $questionnaire = new Questionnaire();
            $questionnaire->setCategory($category);
            $questionnaire->setTypeFormId($typeFormId);
            $questionnaire->setPosition(count($questionnaires) + 1);
        }
        $questionnaire->setType($type);
        $questionnaire->setName($name);
        $questionnaire->setFullName($fullName);
        $this->entityManager->persist($questionnaire);
        $this->entityManager->flush();
        return $questionnaire;
    }

    /**
     * @param string        $typeFormId
     * @param string        $name
     * @param string|null   $description
     * @param Questionnaire $questionnaire
     * @return object|QuestionGroup
     */
    public function findOrCreateQuestionGroup(string $typeFormId, string $name, ?string $description, Questionnaire $questionnaire)
    {
        $questionGroup = $this->entityManager->getRepository(QuestionGroup::class)->findOneBy(['typeFormId' => $typeFormId]);
        if (is_null($questionGroup)) {
            $categories = $this->entityManager->getRepository(QuestionGroup::class)->findBy(['questionnaire' => $questionnaire]);
            $questionGroup = new QuestionGroup();
            $questionGroup->setQuestionnaire($questionnaire);
            $questionGroup->setTypeFormId($typeFormId);
            $questionGroup->setPosition(count($categories) + 1);
        }
        $questionGroup->setName($name);
        $questionGroup->setDescription($description);
        $this->entityManager->persist($questionGroup);
        $this->entityManager->flush();
        return $questionGroup;
    }

    /**
     * @param string        $typeFormId
     * @param string|null   $title
     * @param string        $question
     * @param string        $type
     * @param bool          $required
     * @param array         $properties
     * @param QuestionGroup $questionGroup
     * @return object|Question
     */
    public function findOrCreateQuestion(string $typeFormId, ?string $title, string $question, string $type, bool $required, array $properties, QuestionGroup $questionGroup)
    {

        $questionEntity = $this->entityManager->getRepository(Question::class)->findOneBy(['typeFormId' => $typeFormId]);
        if (is_null($questionEntity)) {
            $questions = $this->entityManager->getRepository(Question::class)->findBy(['questionGroup' => $questionGroup]);
            $questionEntity = new Question();
            $questionEntity->setQuestionGroup($questionGroup);
            $questionEntity->setTypeFormId($typeFormId);
            $questionEntity->setPosition(count($questions) + 1);
        }
        $questionEntity->setType($type);
        $questionEntity->setRequired($required);
        $questionEntity->setTitle($title);
        $questionEntity->setQuestion($question);
        unset($properties['choices']);
        $questionEntity->setProperties($properties);
        $this->entityManager->persist($questionEntity);
        $this->entityManager->flush();
        return $questionEntity;
    }

    /**
     * @param string   $typeFormId
     * @param string   $label
     * @param Question $question
     * @return object|Question
     */
    public function findOrCreateQuestionChoice(string $typeFormId, string $label, Question $question)
    {

        $questionChoiceEntity = $this->entityManager->getRepository(QuestionChoice::class)->findOneBy(['typeFormId' => $typeFormId]);
        if (is_null($questionChoiceEntity)) {
            $questionChoices = $this->entityManager->getRepository(QuestionChoice::class)->findBy(['question' => $question]);
            $questionChoiceEntity = new QuestionChoice();
            $questionChoiceEntity->setQuestion($question);
            $questionChoiceEntity->setTypeFormId($typeFormId);
            $questionChoiceEntity->setPosition(count($questionChoices) + 1);
        }
        $questionChoiceEntity->setChoice($label);
        $this->entityManager->persist($questionChoiceEntity);
        $this->entityManager->flush();
        return $questionChoiceEntity;
    }
}
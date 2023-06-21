<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ReflectionException;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Entity\SchoolType;
use function is_null;

/**
 * QuestionGroup Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionGroupRepository")
 */
class QuestionGroup extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $typeFormId = null;

    /**
     * @var Questionnaire|null
     * @ORM\ManyToOne(targetEntity="Questionnaire", inversedBy="questionGroups")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Questionnaire $questionnaire = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected ?string $name;
    /**
     * @var string|null
     * @ORM\Column(type="string", length=2048, nullable=true)
     */
    protected ?string $description;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var Collection|Question[]
     * @ORM\OneToMany(targetEntity="Question", mappedBy="questionGroup", orphanRemoval=true)
     */
    protected Collection $questions;

    /**
     * @var SchoolType|null
     */
    protected ?SchoolType $currentSchoolType = null;

    /**
     * Questionnaire constructor.
     */
    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return QuestionGroup
     */
    public function setId(?int $id): QuestionGroup
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypeFormId(): ?string
    {
        return $this->typeFormId;
    }

    /**
     * @param string|null $typeFormId
     * @return QuestionGroup
     */
    public function setTypeFormId(?string $typeFormId): QuestionGroup
    {
        $this->typeFormId = $typeFormId;
        return $this;
    }

    /**
     * @return Questionnaire|null
     */
    public function getQuestionnaire(): ?Questionnaire
    {
        return $this->questionnaire;
    }

    /**
     * @param Questionnaire|null $questionnaire
     * @return QuestionGroup
     */
    public function setQuestionnaire(?Questionnaire $questionnaire): QuestionGroup
    {
        $this->questionnaire = $questionnaire;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return QuestionGroup
     */
    public function setName(?string $name): QuestionGroup
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return QuestionGroup
     */
    public function setDescription(?string $description): QuestionGroup
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @param int|null $position
     * @return QuestionGroup
     */
    public function setPosition(?int $position): QuestionGroup
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Collection|Question[]
     */
    public function getQuestions()
    {
        return $this->questions->filter(function (Question $question) {
            return is_null($this->currentSchoolType) || $question->getSchoolTypes()->contains($this->currentSchoolType);
        });
    }

    /**
     * @param Collection|Question[] $questions
     * @return QuestionGroup
     */
    public function setQuestions($questions): QuestionGroup
    {
        $this->questions = $questions;
        return $this;
    }

    /**
     * @return SchoolType|null
     */
    public function getCurrentSchoolType(): ?SchoolType
    {
        return $this->currentSchoolType;
    }

    /**
     * @param SchoolType $currentSchoolType
     * @return QuestionGroup
     */
    public function setCurrentSchoolType(SchoolType $currentSchoolType): QuestionGroup
    {
        $this->currentSchoolType = $currentSchoolType;
        foreach ($this->getQuestions() as $question) {
            $question->setCurrentSchoolType($currentSchoolType);
        }
        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function toArray4Form(): array
    {
        $data = parent::toArray();
        $data['questions'] = [];
        foreach ($this->getQuestions() as $question) {
            $data['questions'][] = $question->toArray4Form();
        }
        return $data;
    }
}
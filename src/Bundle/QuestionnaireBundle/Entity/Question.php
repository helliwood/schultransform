<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ReflectionException;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Entity\SchoolType;
use function is_null;

/**
 * Question Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionRepository")
 */
class Question extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var Collection|SchoolType[]|null
     * @ORM\ManyToMany(targetEntity="Trollfjord\Entity\SchoolType")
     * @ORM\JoinTable(name="question_has_school_type",
     *   joinColumns={@ORM\JoinColumn(name="question_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="school_type", referencedColumnName="name")}
     * )
     */
    protected ?Collection $schoolTypes;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $typeFormId = null;

    /**
     * @var string|null
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $type;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected ?bool $required = false;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $title;

    /**
     * @var string|null
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=1024, nullable=false)
     */
    protected ?string $question;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @var array|null
     */
    protected ?array $properties = null;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var QuestionGroup|null
     * @ORM\ManyToOne(targetEntity="QuestionGroup", inversedBy="questions")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?QuestionGroup $questionGroup = null;

    /**
     * @var Collection|QuestionChoice[]
     * @ORM\OneToMany(targetEntity="QuestionChoice", mappedBy="question", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected Collection $choices;

    /**
     * @var Collection|ResultAnswer[]
     * @ORM\OneToMany(targetEntity="ResultAnswer", mappedBy="question")
     */
    protected Collection $answers;

    /**
     * @var Recommendation|null
     *
     * @ORM\OneToOne(targetEntity="Recommendation", cascade={"persist"}, inversedBy="question")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?Recommendation $recommendation;

    /**
     * @var Recommendation|null
     *
     * @ORM\OneToOne(targetEntity="Recommendation", cascade={"persist"}, inversedBy="questionAdvanced")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?Recommendation $advancedRecommendation;


    /**
     * @var Collection|QuestionOverride[]
     * @ORM\OneToMany(targetEntity="QuestionOverride", mappedBy="override", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $overrides;

    /**
     * @var SchoolType|null
     */
    protected ?SchoolType $currentSchoolType = null;

    /**
     * Questionnaire constructor.
     */
    public function __construct()
    {
        $this->schoolTypes = new ArrayCollection();
        $this->choices = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->overrides = new ArrayCollection();
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
     * @return Question
     */
    public function setId(?int $id): Question
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Collection|SchoolType[]|null
     */
    public function getSchoolTypes()
    {
        return $this->schoolTypes;
    }

    /**
     * @param Collection|SchoolType[]|null $schoolTypes
     * @return Question
     */
    public function setSchoolTypes($schoolTypes): Question
    {
        $this->schoolTypes = $schoolTypes;
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
     * @return Question
     */
    public function setTypeFormId(?string $typeFormId): Question
    {
        $this->typeFormId = $typeFormId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return Question
     */
    public function setType(?string $type): Question
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired(): ?bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     * @return Question
     */
    public function setRequired(?bool $required): Question
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return Question
     */
    public function setTitle(?string $title): Question
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getQuestion(): ?string
    {
        $override = $this->getOverrideBySchoolType($this->getCurrentSchoolType());
        if (! is_null($override) && ! is_null($override->getQuestion())) {
            return $override->getQuestion();
        }
        return $this->question;
    }

    /**
     * @param string|null $question
     * @return Question
     */
    public function setQuestion(?string $question): Question
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param array|null $properties
     * @return Question
     */
    public function setProperties(?array $properties): Question
    {
        $this->properties = $properties;
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
     * @return Question
     */
    public function setPosition(?int $position): Question
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return QuestionGroup|null
     */
    public function getQuestionGroup(): ?QuestionGroup
    {
        return $this->questionGroup;
    }

    /**
     * @param QuestionGroup|null $questionGroup
     * @return Question
     */
    public function setQuestionGroup(?QuestionGroup $questionGroup): Question
    {
        $this->questionGroup = $questionGroup;
        return $this;
    }

    /**
     * @return Collection|QuestionChoice[]
     */
    public function getChoices()
    {
        return $this->choices->filter(function (QuestionChoice $choice) {
            $choiceOverride = $choice->getOverrideBySchoolType($this->getCurrentSchoolType());
            return is_null($this->getCurrentSchoolType()) ||
                (is_null($choiceOverride) || $choiceOverride->isHide() === false);
        });
    }

    /**
     * @param Collection|QuestionChoice[] $choices
     * @return Question
     */
    public function setChoices($choices): Question
    {
        $this->choices = $choices;
        return $this;
    }

    /**
     * @return $this
     */
    public function reorderChoices(): Question
    {
        $pos = 1;
        foreach ($this->getChoices() as $choice) {
            $choice->setPosition($pos);
            $pos++;
        }
        return $this;
    }

    /**
     * @return Collection|ResultAnswer[]
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param Collection|ResultAnswer[] $answers
     * @return Question
     */
    public function setAnswers($answers): Question
    {
        $this->answers = $answers;
        return $this;
    }


    /**
     * @return Recommendation|null
     */
    public function getRecommendation(): ?Recommendation
    {
        return $this->recommendation;
    }

    /**
     * @param Recommendation|null $recommendation
     */
    public function setRecommendation(?Recommendation $recommendation): void
    {
        $this->recommendation = $recommendation;
    }

    /**
     * @return Recommendation|null
     */
    public function getAdvancedRecommendation(): ?Recommendation
    {
        return $this->advancedRecommendation;
    }

    /**
     * @param Recommendation|null $advancedRecommendation
     */
    public function setAdvancedRecommendation(?Recommendation $advancedRecommendation): void
    {
        $this->advancedRecommendation = $advancedRecommendation;
    }

    /**
     * @return Collection|QuestionOverride[]
     */
    public function getOverrides()
    {
        return $this->overrides;
    }

    /**
     * @param QuestionOverride $override
     * @return $this
     */
    public function addOverride(QuestionOverride $override): Question
    {
        $override->setOverride($this);
        $this->overrides->add($override);
        return $this;
    }

    /**
     * @param QuestionOverride $override
     * @return $this
     */
    public function removeOverride(QuestionOverride $override): Question
    {
        if ($this->overrides->contains($override)) {
            $this->overrides->removeElement($override);
        }
        return $this;
    }

    /**
     * @param SchoolType|null $schoolType
     * @return QuestionOverride|null
     */
    public function getOverrideBySchoolType(?SchoolType $schoolType): ?QuestionOverride
    {
        foreach ($this->getOverrides() as $override) {
            if ($override->getSchoolType() === $schoolType) {
                return $override;
            }
        }
        return null;
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
     * @return Question
     */
    public function setCurrentSchoolType(SchoolType $currentSchoolType): Question
    {
        $this->currentSchoolType = $currentSchoolType;
        foreach ($this->getChoices() as $choice) {
            $choice->setCurrentSchoolType($currentSchoolType);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function stats(): array
    {
        $stats = [];
        if ($this->getType() === 'opinion_scale') {
            foreach ($this->getAnswers() as $answer) {
                if (isset($stats[$answer->getValue()])) {
                    $stats[$answer->getValue()]['count']++;
                } else {
                    $stats[$answer->getValue()] = ['value' => $answer->getValue(), 'count' => 1];
                }
            }
        }
        ksort($stats);
        return $stats;
    }

    /**
     * @throws ReflectionException
     */
    public function toArray4Form(): array
    {
        $data = parent::toArray();
        $data['choices'] = [];
        foreach ($this->getChoices() as $choice) {
            $data['choices'][] = $choice->toArray();
        }
        return $data;
    }
}

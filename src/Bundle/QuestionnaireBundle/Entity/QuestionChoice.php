<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Entity\SchoolType;
use function is_null;

/**
 * QuestionChoice Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity()
 */
class QuestionChoice extends AbstractEntity
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
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=false)
     */
    protected ?string $choice;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var Question|null
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="choices")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Question $question = null;

    /**
     * @var Collection|ResultAnswer[]
     * @ORM\OneToMany(targetEntity="ResultAnswer", mappedBy="choice")
     */
    protected Collection $answers;

    /**
     * @var Collection|QuestionChoiceOverride[]
     * @ORM\OneToMany(targetEntity="Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionChoiceOverride", mappedBy="override", cascade={"persist"}, orphanRemoval=true)
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
     * @return QuestionChoice
     */
    public function setId(?int $id): QuestionChoice
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
     * @return QuestionChoice
     */
    public function setTypeFormId(?string $typeFormId): QuestionChoice
    {
        $this->typeFormId = $typeFormId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getChoice(): ?string
    {
        $override = $this->getOverrideBySchoolType($this->getCurrentSchoolType());
        if (! is_null($override) && ! is_null($override->getChoice())) {
            return $override->getChoice();
        }
        return $this->choice;
    }

    /**
     * @param string|null $choice
     * @return QuestionChoice
     */
    public function setChoice(?string $choice): QuestionChoice
    {
        $this->choice = $choice;
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
     * @return QuestionChoice
     */
    public function setPosition(?int $position): QuestionChoice
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Question|null
     */
    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    /**
     * @param Question|null $question
     * @return QuestionChoice
     */
    public function setQuestion(?Question $question): QuestionChoice
    {
        $this->question = $question;
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
     * @return QuestionChoice
     */
    public function setAnswers($answers): QuestionChoice
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return Collection|QuestionChoiceOverride[]
     */
    public function getOverrides()
    {
        return $this->overrides;
    }

    /**
     * @param Collection|QuestionChoiceOverride[] $overrides
     * @return QuestionChoice
     */
    public function setOverrides($overrides): QuestionChoice
    {
        $this->overrides = $overrides;
        return $this;
    }

    /**
     * @param QuestionChoiceOverride $override
     * @return $this
     */
    public function addOverride(QuestionChoiceOverride $override): QuestionChoice
    {
        $override->setOverride($this);
        $this->overrides->add($override);
        return $this;
    }

    /**
     * @param QuestionChoiceOverride $override
     * @return $this
     */
    public function removeOverride(QuestionChoiceOverride $override): QuestionChoice
    {
        if ($this->overrides->contains($override)) {
            $this->overrides->removeElement($override);
        }
        return $this;
    }

    /**
     * @param SchoolType|null $schoolType
     * @return QuestionChoiceOverride|null
     */
    public function getOverrideBySchoolType(?SchoolType $schoolType): ?QuestionChoiceOverride
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
     * @return QuestionChoice
     */
    public function setCurrentSchoolType(SchoolType $currentSchoolType): QuestionChoice
    {
        $this->currentSchoolType = $currentSchoolType;
        return $this;
    }
}
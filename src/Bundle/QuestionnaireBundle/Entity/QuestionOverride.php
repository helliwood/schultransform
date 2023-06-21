<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Entity\SchoolType;

/**
 * QuestionOverride Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity()
 */
class QuestionOverride extends AbstractEntity
{
    /**
     * @var Question|null
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="overrides")
     * @ORM\JoinColumn(name="question_id", nullable=false, onDelete="CASCADE")
     */
    protected ?Question $override = null;

    /**
     * @var SchoolType|null
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Trollfjord\Entity\SchoolType")
     * @ORM\JoinColumn(referencedColumnName="name", name="school_type", nullable=false, onDelete="RESTRICT")
     * @Assert\NotBlank
     */
    protected ?SchoolType $schoolType = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=true)
     * @Assert\NotBlank
     */
    protected ?string $question = null;

    /**
     * @return Question|null
     */
    public function getOverride(): ?Question
    {
        return $this->override;
    }

    /**
     * @param Question|null $override
     * @return QuestionOverride
     */
    public function setOverride(?Question $override): QuestionOverride
    {
        $this->override = $override;
        return $this;
    }

    /**
     * @return SchoolType|null
     */
    public function getSchoolType(): ?SchoolType
    {
        return $this->schoolType;
    }

    /**
     * @param SchoolType|null $schoolType
     * @return QuestionOverride
     */
    public function setSchoolType(?SchoolType $schoolType): QuestionOverride
    {
        $this->schoolType = $schoolType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getQuestion(): ?string
    {
        return $this->question;
    }

    /**
     * @param string|null $question
     * @return QuestionOverride
     */
    public function setQuestion(?string $question): QuestionOverride
    {
        $this->question = $question;
        return $this;
    }
}
<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Entity\SchoolType;

/**
 * QuestionChoiceOverride Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity()
 */
class QuestionChoiceOverride extends AbstractEntity
{
    /**
     * @var QuestionChoice|null
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="QuestionChoice", inversedBy="overrides")
     * @ORM\JoinColumn(name="question_chocie_id", nullable=false, onDelete="CASCADE")
     */
    protected ?QuestionChoice $override = null;

    /**
     * @var SchoolType|null
     * @ORM\Id()
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Trollfjord\Entity\SchoolType")
     * @ORM\JoinColumn(referencedColumnName="name", name="school_type", nullable=false, onDelete="RESTRICT")
     */
    protected ?SchoolType $schoolType = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    protected ?string $choice = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $hide = false;

    /**
     * @return QuestionChoice|null
     */
    public function getOverride(): ?QuestionChoice
    {
        return $this->override;
    }

    /**
     * @param QuestionChoice|null $override
     * @return QuestionChoiceOverride
     */
    public function setOverride(?QuestionChoice $override): QuestionChoiceOverride
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
     * @return QuestionChoiceOverride
     */
    public function setSchoolType(?SchoolType $schoolType): QuestionChoiceOverride
    {
        $this->schoolType = $schoolType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getChoice(): ?string
    {
        return $this->choice;
    }

    /**
     * @param string|null $choice
     * @return QuestionChoiceOverride
     */
    public function setChoice(?string $choice): QuestionChoiceOverride
    {
        $this->choice = $choice;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHide(): bool
    {
        return $this->hide;
    }

    /**
     * @param bool $hide
     * @return QuestionChoiceOverride
     */
    public function setHide(bool $hide): QuestionChoiceOverride
    {
        $this->hide = $hide;
        return $this;
    }
}
<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ReflectionException;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Core\Entity\User;

/**
 * Recommendation Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\QuestionnaireBundle\Repository\RecommendationRepository")
 */
class Recommendation extends AbstractEntity
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
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     * @ORM\Column(type="string", length=200, nullable=false)
     */
    protected ?string $title = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $implementation = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $tips = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $tipsSchoolManagement = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $examples = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $additionalInformation = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected ?DateTime $creationDate = null;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Trollfjord\Core\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected ?User $createdBy = null;

    /**
     * @var Question|null
     *
     * @ORM\OneToOne(targetEntity="Question", cascade={"persist"}, mappedBy="recommendation")
     */
    protected ?Question $question = null;

    /**
     * @var Question|null
     *
     * @ORM\OneToOne(targetEntity="Question", cascade={"persist"}, mappedBy="advancedRecommendation")
     */
    protected ?Question $questionAdvanced = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $advanced = false;

    /**
     * Recommendation constructor.
     */
    public function __construct()
    {
        $this->creationDate = new DateTime();
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
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
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
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
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
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getImplementation(): ?string
    {
        return $this->implementation;
    }

    /**
     * @param string|null $implementation
     */
    public function setImplementation(?string $implementation): void
    {
        $this->implementation = $implementation;
    }

    /**
     * @return string|null
     */
    public function getTips(): ?string
    {
        return $this->tips;
    }

    /**
     * @param string|null $tips
     */
    public function setTips(?string $tips): void
    {
        $this->tips = $tips;
    }

    /**
     * @return string|null
     */
    public function getTipsSchoolManagement(): ?string
    {
        return $this->tipsSchoolManagement;
    }

    /**
     * @param string|null $tipsSchoolManagement
     */
    public function setTipsSchoolManagement(?string $tipsSchoolManagement): void
    {
        $this->tipsSchoolManagement = $tipsSchoolManagement;
    }

    /**
     * @return string|null
     */
    public function getExamples(): ?string
    {
        return $this->examples;
    }

    /**
     * @param string|null $examples
     */
    public function setExamples(?string $examples): void
    {
        $this->examples = $examples;
    }

    /**
     * @return string|null
     */
    public function getAdditionalInformation(): ?string
    {
        return $this->additionalInformation;
    }

    /**
     * @param string|null $additionalInformation
     */
    public function setAdditionalInformation(?string $additionalInformation): void
    {
        $this->additionalInformation = $additionalInformation;
    }

    /**
     * @return DateTime|null
     */
    public function getCreationDate(): ?DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param DateTime|null $creationDate
     */
    public function setCreationDate(?DateTime $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param User|null $createdBy
     */
    public function setCreatedBy(?User $createdBy): void
    {
        $this->createdBy = $createdBy;
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
     */
    public function setQuestionAdvanced(?Question $question): void
    {
        $this->questionAdvanced = $question;
    }

    /**
     * @return Question|null
     */
    public function getQuestionAdvanced(): ?Question
    {
        return $this->questionAdvanced;
    }

    /**
     * @param Question|null $question
     */
    public function setQuestion(?Question $question): void
    {
        $this->question = $question;
    }


    /**
     * @return bool
     */
    public function isAdvanced(): bool
    {
        return $this->advanced;
    }

    /**
     * @param bool $advanced
     * @return Recommendation
     */
    public function setAdvanced(bool $advanced): Recommendation
    {
        $this->advanced = $advanced;
        return $this;
    }


    /**
     * @return string|null
     */
    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function jsonSerialize(): array
    {
        $data = parent::toArray();
        $data['createdBy'] = $this->getCreatedBy()->getUsername();
        return $data;
    }
}

<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * ResultAnswer Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\QuestionnaireBundle\Repository\ResultAnswerRepository")
 */
class ResultAnswer extends AbstractEntity
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
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $type = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    protected ?string $value = null;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected ?\DateTime $creationDate;

    /**
     * @var Result|null
     * @ORM\ManyToOne(targetEntity="Result", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Result $result;

    /**
     * @var Question|null
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected ?Question $question;

    /**
     * @var QuestionChoice|null
     * @ORM\ManyToOne(targetEntity="QuestionChoice", inversedBy="answers")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected ?QuestionChoice $choice = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $skipped = false;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $skipReason = null;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->creationDate = new \DateTime();
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
     * @return ResultAnswer
     */
    public function setId(?int $id): ResultAnswer
    {
        $this->id = $id;
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
     * @return ResultAnswer
     */
    public function setType(?string $type): ResultAnswer
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     * @return ResultAnswer
     */
    public function setValue(?string $value): ResultAnswer
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime|null $creationDate
     * @return ResultAnswer
     */
    public function setCreationDate(?\DateTime $creationDate): ResultAnswer
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return Result|null
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }

    /**
     * @param Result|null $result
     * @return ResultAnswer
     */
    public function setResult(?Result $result): ResultAnswer
    {
        $this->result = $result;
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
     * @return ResultAnswer
     */
    public function setQuestion(?Question $question): ResultAnswer
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    /**
     * @param bool $skipped
     * @return ResultAnswer
     */
    public function setSkipped(bool $skipped): ResultAnswer
    {
        $this->skipped = $skipped;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSkipReason(): ?string
    {
        return $this->skipReason;
    }

    /**
     * @param string|null $skipReason
     * @return ResultAnswer
     */
    public function setSkipReason(?string $skipReason): ResultAnswer
    {
        $this->skipReason = $skipReason;
        return $this;
    }

    /**
     * @return QuestionChoice|null
     */
    public function getChoice(): ?QuestionChoice
    {
        return $this->choice;
    }

    /**
     * @param QuestionChoice|null $choice
     * @return ResultAnswer
     */
    public function setChoice(?QuestionChoice $choice): ResultAnswer
    {
        $this->choice = $choice;
        return $this;
    }
}
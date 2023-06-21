<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Core\Entity\AbstractEntity;
use function array_sum;
use function count;
use function is_numeric;
use function iterator_to_array;

/**
 * Result Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\QuestionnaireBundle\Repository\ResultRepository")
 */
class Result extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected ?int $rating = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected ?bool $shareWithSchoolAuthority = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected ?bool $shareNotices = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected ?bool $share = false;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected ?DateTime $creationDate;

    /**
     * @var Questionnaire|null
     * @ORM\ManyToOne(targetEntity="Questionnaire", inversedBy="results")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected ?Questionnaire $questionnaire = null;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="Trollfjord\Bundle\PublicUserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?User $user = null;

    /**
     * @var Collection|null|ResultAnswer[]
     * @ORM\OneToMany(targetEntity="ResultAnswer", mappedBy="result", orphanRemoval=true)
     */
    protected ?Collection $answers;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->creationDate = new DateTime();
        $this->answers = new ArrayCollection();
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
     * @return Result
     */
    public function setId(?int $id): Result
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRating(): ?int
    {
        return $this->rating;
    }

    /**
     * @param int|null $rating
     * @return Result
     */
    public function setRating(?int $rating): Result
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isShareWithSchoolAuthority(): ?bool
    {
        return $this->shareWithSchoolAuthority;
    }

    /**
     * @param bool|null $shareWithSchoolAuthority
     * @return Result
     */
    public function setShareWithSchoolAuthority(?bool $shareWithSchoolAuthority): Result
    {
        $this->shareWithSchoolAuthority = $shareWithSchoolAuthority;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isShareNotices(): ?bool
    {
        return $this->shareNotices;
    }

    /**
     * @param bool|null $shareNotices
     * @return Result
     */
    public function setShareNotices(?bool $shareNotices): Result
    {
        $this->shareNotices = $shareNotices;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isShare(): ?bool
    {
        return $this->share;
    }

    /**
     * @param bool|null $share
     * @return void
     */
    public function setShare(?bool $share): void
    {
        $this->share = $share;
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
     * @return Result
     */
    public function setCreationDate(?DateTime $creationDate): Result
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return Questionnaire|null
     */
    public function getQuestionnaire(): ?Questionnaire
    {
        if ($this->questionnaire instanceof Questionnaire) {
            $this->questionnaire->setCurrentSchoolType($this->getUser()->getSchoolType());
        }
        return $this->questionnaire;
    }

    /**
     * @param Questionnaire|null $questionnaire
     * @return Result
     */
    public function setQuestionnaire(?Questionnaire $questionnaire): Result
    {
        $this->questionnaire = $questionnaire;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return Result
     */
    public function setUser(?User $user): Result
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection|ResultAnswer[]|null
     * @throws Exception
     */
    public function getAnswers()
    {
        // Anworten nach Gruppe und Order sortieren
        $iterator = $this->answers->getIterator();
        $iterator->uasort(function (ResultAnswer $first, ResultAnswer $second) {
            if ($first->getQuestion()->getQuestionGroup()->getPosition() > $second->getQuestion()->getQuestionGroup()->getPosition()) {
                return 1;
            } elseif ($first->getQuestion()->getQuestionGroup()->getPosition() == $second->getQuestion()->getQuestionGroup()->getPosition()) {
                if ($first->getQuestion()->getPosition() > $second->getQuestion()->getPosition()) {
                    return 1;
                } elseif ($first->getQuestion()->getPosition() == $second->getQuestion()->getPosition()) {
                    return 0;
                } else {
                    return -1;
                }
            } else {
                return -1;
            }
        });
        $this->answers = new ArrayCollection(iterator_to_array($iterator));

        return $this->answers;
    }

    /**
     * @param Collection|ResultAnswer[]|null $answers
     * @return Result
     */
    public function setAnswers($answers): Result
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @param int|null $answerValueIsSmallerThan
     * @return array|Recommendation[]
     */
    public function getRecommendations(?int $answerValueIsSmallerThan = 4): array
    {
        $recommendations = [];
        foreach ($this->getAnswers() as $answer) {
            if ($answer->getType() == "value" &&
                $answer->getValue() < $answerValueIsSmallerThan &&
                $answer->getQuestion()->getRecommendation()) {
                $recommendations[] = $answer->getQuestion()->getRecommendation();
            }
        }
        return $recommendations;
    }


    /**
     * @return number
     */
    public function getAnswersAvg(): float
    {
        $a = [];
        foreach ($this->getAnswers() as $answer) {
            if ($answer->getType() == "value" && is_numeric($answer->getValue())) {
                $a[] = $answer->getValue();
            }
        }

        if (count($a) > 0) {
            return array_sum($a) / count($a);
        } else {
            return 0;
        }

    }
}
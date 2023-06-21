<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use ReflectionException;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Entity\SchoolType;

/**
 * Questionnaire Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionnaireRepository")
 */
class Questionnaire extends AbstractEntity implements JsonSerializable
{
    public const TYPE_SCHOOL = 'school';
    public const TYPE_SCHOOL_BOARD = 'school_board';

    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=false, options={"default":"school"})
     */
    protected ?string $type = self::TYPE_SCHOOL;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $typeFormId = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $icon;

    /**
     * @var Category|null
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="questionnaires")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Category $category = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     * @Assert\NotBlank
     */
    protected ?string $name;
    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $fullName;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var Collection|QuestionGroup[]
     * @ORM\OneToMany(targetEntity="QuestionGroup", mappedBy="questionnaire", orphanRemoval=true)
     */
    protected Collection $questionGroups;

    /**
     * @var Site|null
     * @ORM\ManyToOne(targetEntity="Trollfjord\Bundle\ContentTreeBundle\Entity\Site")
     */
    protected ?Site $site;


    /**
     * @var Collection|Result[]
     * @ORM\OneToMany(targetEntity="Result", mappedBy="questionnaire")
     */
    protected Collection $results;

    /**
     * @var SchoolType|null
     */
    protected ?SchoolType $currentSchoolType = null;

    /**
     * Questionnaire constructor.
     */
    public function __construct()
    {
        $this->questionGroups = new ArrayCollection();
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
     * @return Questionnaire
     */
    public function setId(?int $id): Questionnaire
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
     * @return Questionnaire
     */
    public function setType(?string $type): Questionnaire
    {
        $this->type = $type;
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
     * @return Questionnaire
     */
    public function setTypeFormId(?string $typeFormId): Questionnaire
    {
        $this->typeFormId = $typeFormId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     * @return Questionnaire
     */
    public function setIcon(?string $icon): Questionnaire
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     * @return Questionnaire
     */
    public function setCategory(?Category $category): Questionnaire
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Site|null
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * @param Site|null $helppage
     * @return Site
     */
    public function setSite(?Site $site): Questionnaire
    {
        $this->site = $site;
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
     * @return Questionnaire
     */
    public function setName(?string $name): Questionnaire
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @param string|null $fullName
     * @return Questionnaire
     */
    public function setFullName(?string $fullName): Questionnaire
    {
        $this->fullName = $fullName;
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
     * @return Questionnaire
     */
    public function setPosition(?int $position): Questionnaire
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Collection|QuestionGroup[]
     */
    public function getQuestionGroups()
    {
        return $this->questionGroups;
    }

    /**
     * @param Collection|QuestionGroup[] $questionGroups
     * @return Questionnaire
     */
    public function setQuestionGroups($questionGroups): Questionnaire
    {
        $this->questionGroups = $questionGroups;
        return $this;
    }

    /**
     * @return Collection|Result[]
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param Collection|Result[] $results
     * @return Questionnaire
     */
    public function setResults($results): Questionnaire
    {
        $this->results = $results;
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
     * @return Questionnaire
     */
    public function setCurrentSchoolType(SchoolType $currentSchoolType): Questionnaire
    {
        $this->currentSchoolType = $currentSchoolType;
        foreach ($this->getQuestionGroups() as $questionGroup) {
            $questionGroup->setCurrentSchoolType($currentSchoolType);
        }
        return $this;
    }

    /**
     * @return array|mixed
     * @throws ReflectionException
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['resultCount'] = $this->getResults()->count();
        $data['questionGroupsCount'] = $this->getQuestionGroups()->count();
        return $data;
    }

    /**
     * @throws ReflectionException
     */
    public function toArray4Form(): array
    {
        $data = parent::toArray();
        $data['category'] = $this->getCategory();
        $data['schooltype'] = $this->getCurrentSchoolType();
        $data['questionGroups'] = [];
        foreach ($this->getQuestionGroups() as $questionGroup) {
            $data['questionGroups'][] = $questionGroup->toArray4Form();
        }

        return $data;
    }

    /**
     * Muss dringend die Id sein!
     * @return int|string|null
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
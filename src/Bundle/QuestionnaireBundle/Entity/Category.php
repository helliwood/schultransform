<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * Category Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\QuestionnaireBundle\Repository\CategoryRepository")
 */
class Category extends AbstractEntity
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
     * @var Category|null
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected ?Category $parent = null;

    /**
     * @var Category[]|Collection|null
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent", orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"position":"ASC"})
     */
    protected ?Collection $children;

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $name;

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $fullName;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected ?string $color;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $icon;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Questionnaire", mappedBy="category", orphanRemoval=true)
     */
    protected Collection $questionnaires;


    /**
     * @var Site|null
     * @ORM\ManyToOne(targetEntity="Trollfjord\Bundle\ContentTreeBundle\Entity\Site")
     */
    protected ?Site $site;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $intro;


    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->questionnaires = new ArrayCollection();
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
     * @return Category
     */
    public function setId(?int $id): Category
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
     * @return Category
     */
    public function setTypeFormId(?string $typeFormId): Category
    {
        $this->typeFormId = $typeFormId;
        return $this;
    }

    /**
     * @return Category|null
     */
    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * @param Category|null $parent
     * @return Category
     */
    public function setParent(?Category $parent): Category
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection|Category[]|null
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Collection|Category[]|null $children
     * @return Category
     */
    public function setChildren($children)
    {
        $this->children = $children;
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
     * @return Category
     */
    public function setName(?string $name): Category
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
     * @return Category
     */
    public function setFullName(?string $fullName): Category
    {
        $this->fullName = $fullName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string|null $color
     * @return Category
     */
    public function setColor(?string $color): Category
    {
        $this->color = $color;
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
     * @return Category
     */
    public function setIcon(?string $icon): Category
    {
        $this->icon = $icon;
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
     * @return Category
     */
    public function setPosition(?int $position): Category
    {
        $this->position = $position;
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
    public function setSite(?Site $site): Category
    {
        $this->site = $site;
        return $this;
    }


    /**
     * @return string|null
     */
    public function getIntro(): ?string
    {
        return $this->intro;
    }

    /**
     * @param string|null $icon
     * @return Category
     */
    public function setIntro(?string $intro): Category
    {
        $this->intro = $intro;
        return $this;
    }

    /**
     * @return Collection|Questionnaire[]
     */
    public function getQuestionnaires()
    {
        return $this->questionnaires;
    }

    /**
     * @param Collection $questionnaires
     * @return Category
     */
    public function setQuestionnaires(Collection $questionnaires): Category
    {
        $this->questionnaires = $questionnaires;
        return $this;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function jsonSerialize(): array
    {
        $data = parent::toArray();
        $data['categoryCount'] = $this->getChildren()->count();
        $data['questionnaireCount'] = $this->getQuestionnaires()->count();
        $results = 0;
        foreach ($this->getQuestionnaires() as $questionnaire) {
            $results += $questionnaire->getResults()->count();
        }
        $data['resultCount'] = $results;
        return $data;
    }

    public function getTeacherQuestionnaires(): array
    {
        $toReturn = [];
        //count all questionnaires to avoid the loop if empty
        if (count($this->getQuestionnaires()) > 0) {
            foreach ($this->getQuestionnaires() as $questionnaire) {
                //check for the typ school
                if ($questionnaire->getType() === 'school') {
                    $toReturn[] = $questionnaire;
                }
            }
        }
        return $toReturn;
    }
    public function getTeacherQuestionnairesSchoolAuthority(): array
    {
        $toReturn = [];
        //count all questionnaires to avoid the loop if empty
        if (count($this->getQuestionnaires()) > 0) {
            foreach ($this->getQuestionnaires() as $questionnaire) {
                //check for the typ school
                if ($questionnaire->getType() === 'school_board') {
                    $toReturn[] = $questionnaire;
                }
            }
        }
        return $toReturn;
    }
    public function getSchoolAuthorityQuestionnaires(): array
    {
        $toReturn = [];
        //count all questionnaires to avoid the loop if empty
        if (count($this->getQuestionnaires()) > 0) {
            foreach ($this->getQuestionnaires() as $questionnaire) {
                //check for the typ school
                if ($questionnaire->getType() === 'school_board') {
                    $toReturn[] = $questionnaire;
                }
            }
        }
        return $toReturn;
    }
}

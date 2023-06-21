<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Core\Entity\AbstractEntity;
use function is_null;

/**
 * SitePublished Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Entity
 *
 * @ORM\Table(uniqueConstraints={
 *   @UniqueConstraint(columns={"parent_id", "name"}),
 *   @UniqueConstraint(columns={"parent_id", "slug"})
 * })
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\ContentTreeBundle\Repository\SitePublishedRepository")
 */
class SitePublished extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var Site|null
     * @ORM\OneToOne(targetEntity="Site", inversedBy="publishedSite")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Site $site = null;

    /**
     * @var SitePublished|null
     * @ORM\ManyToOne(targetEntity="SitePublished", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected ?SitePublished $parent = null;

    /**
     * @var SitePublished[]|Collection|null
     * @ORM\OneToMany(targetEntity="SitePublished", mappedBy="parent", orphanRemoval=true, indexBy="site")
     * @ORM\OrderBy({"position":"ASC"})
     */
    protected ?Collection $children;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     * @Assert\NotBlank
     */
    protected ?string $name = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     * @Assert\NotBlank
     */
    protected ?string $slug = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Callback({"Trollfjord\Bundle\ContentTreeBundle\Entity\Site", "validateAlternativeRoute"})
     */
    protected ?string $alternativeRoute = null;

    /**
     * @var Media|null
     * @ORM\ManyToOne(targetEntity="Trollfjord\Bundle\MediaBaseBundle\Entity\Media")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected ?Media $socialMediaImage = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $dcTitle = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $dcCreator = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $dcDate = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $dcKeywords = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=true)
     * @Assert\Length(max="512")
     */
    protected ?string $dcDescription = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @var array|null
     */
    protected ?array $data = null;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected ?bool $published = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     */
    protected ?bool $menuEntry = true;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $publishDate;

    /**
     * Site constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->publishDate = new DateTime();
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
     * @return Site|null
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * @param Site|null $site
     */
    public function setSite(?Site $site): void
    {
        $this->site = $site;
    }

    /**
     * @return SitePublished|null
     */
    public function getParent(): ?SitePublished
    {
        return $this->parent;
    }

    /**
     * @param SitePublished|null $parent
     */
    public function setParent(?SitePublished $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Collection|SitePublished[]|null
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Collection|SitePublished[]|null $children
     */
    public function setChildren($children): void
    {
        $this->children = $children;
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
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     */
    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return string|null
     */
    public function getAlternativeRoute(): ?string
    {
        return $this->alternativeRoute;
    }

    /**
     * @param string|null $alternativeRoute
     */
    public function setAlternativeRoute(?string $alternativeRoute): void
    {
        $this->alternativeRoute = $alternativeRoute;
    }

    /**
     * @return Media|null
     */
    public function getSocialMediaImage(): ?Media
    {
        return $this->socialMediaImage;
    }

    /**
     * @param Media|null $socialMediaImage
     */
    public function setSocialMediaImage(?Media $socialMediaImage): void
    {
        $this->socialMediaImage = $socialMediaImage;
    }

    /**
     * @return string|null
     */
    public function getDcTitle(): ?string
    {
        return $this->dcTitle;
    }

    /**
     * @param string|null $dcTitle
     */
    public function setDcTitle(?string $dcTitle): void
    {
        $this->dcTitle = $dcTitle;
    }

    /**
     * @return string|null
     */
    public function getDcCreator(): ?string
    {
        return $this->dcCreator;
    }

    /**
     * @param string|null $dcCreator
     */
    public function setDcCreator(?string $dcCreator): void
    {
        $this->dcCreator = $dcCreator;
    }

    /**
     * @return DateTime|null
     */
    public function getDcDate(): ?DateTime
    {
        return $this->dcDate;
    }

    /**
     * @param DateTime|null $dcDate
     */
    public function setDcDate(?DateTime $dcDate): void
    {
        $this->dcDate = $dcDate;
    }

    /**
     * @return string|null
     */
    public function getDcKeywords(): ?string
    {
        return $this->dcKeywords;
    }

    /**
     * @param string|null $dcKeywords
     */
    public function setDcKeywords(?string $dcKeywords): void
    {
        $this->dcKeywords = $dcKeywords;
    }

    /**
     * @return string|null
     */
    public function getDcDescription(): ?string
    {
        return $this->dcDescription;
    }

    /**
     * @param string|null $dcDescription
     */
    public function setDcDescription(?string $dcDescription): void
    {
        $this->dcDescription = $dcDescription;
    }

    /**
     * @return string[]|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param string[]|null $data
     * @return SitePublished
     */
    public function setData(?array $data): SitePublished
    {
        $this->data = $data;

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
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return bool
     */
    public function isPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * @param bool $published
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    /**
     * @return bool
     */
    public function isMenuEntry(): ?bool
    {
        return $this->menuEntry;
    }

    /**
     * @param bool $menuEntry
     */
    public function setMenuEntry(?bool $menuEntry): void
    {
        $this->menuEntry = $menuEntry;
    }

    /**
     * @return DateTime|null
     */
    public function getPublishDate(): ?DateTime
    {
        return $this->publishDate;
    }

    /**
     * @param DateTime|null $publishDate
     */
    public function setPublishDate(?DateTime $publishDate): void
    {
        $this->publishDate = $publishDate;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        $url = '/' . $this->getSlug();
        $parent = $this->getParent();
        while (! is_null($parent)) {
            $url = '/' . $parent->getSlug() . $url;
            $parent = $parent->getParent();
        }
        return $url;
    }

    /**
     * @return string
     */
    public function getRouteName(): string
    {
        return $this->getSite()->getRouteName();
    }

    /**
     * @param int $relationDepth
     * @return string[]
     */
    public function toArray(int $relationDepth = 0): array
    {
        $data = parent::toArray($relationDepth);
        $data['route'] = $this->getRouteName();
        return $data;
    }

    /**
     * Hier muss unbedingt die Id zurückgegeben werden!
     * @return string|null
     */
    public function __toString(): string
    {
        return $this->getId();
    }
}

<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use ReflectionException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Core\Entity\AbstractEntity;
use function count;
use function explode;
use function iconv;
use function is_null;
use function is_numeric;
use function method_exists;
use function preg_replace;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Site Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Entity
 *
 * @ORM\Table(uniqueConstraints={
 *   @UniqueConstraint(columns={"parent_id", "name", "deleted"}),
 *   @UniqueConstraint(columns={"parent_id", "slug", "deleted"})
 * })
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\ContentTreeBundle\Repository\SiteRepository")
 */
class Site extends AbstractEntity
{
    /**
     * Prefix for Routes
     */
    public const ROUTE_PREFIX = 'content_tree_public_site-';

    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var Site|null
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected ?Site $parent = null;

    /**
     * @var Site[]|Collection|null
     * @ORM\OneToMany(targetEntity="Site", mappedBy="parent", orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"position":"ASC"})
     */
    protected ?Collection $children;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     * @Assert\NotBlank
     */
    protected ?string $name;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     * @Assert\NotBlank
     */
    protected ?string $slug;

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
    protected ?Media $socialMediaImage;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $dcTitle;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $dcCreator;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $dcDate;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $dcKeywords;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024, nullable=true)
     * @Assert\Length(max="512")
     */
    protected ?string $dcDescription;

    /**
     * @var SiteContent[]|Collection|null
     * @ORM\OneToMany(targetEntity="SiteContent", mappedBy="site", cascade={"persist"}, orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"position":"ASC"})
     */
    protected ?Collection $content;

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
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected ?bool $deleted = false;

    /**
     * @var SiteHistory[]|Collection|null
     * @ORM\OneToMany(targetEntity="SiteHistory", mappedBy="site")
     * @ORM\OrderBy({"date":"DESC"})
     */
    protected ?Collection $history;

    /**
     * @var SitePublished|null
     * @ORM\OneToOne(targetEntity="SitePublished", mappedBy="site", cascade={"persist"})
     */
    protected ?SitePublished $publishedSite = null;

    /**
     * Site constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->content = new ArrayCollection();
        $this->history = new ArrayCollection();
        $this->dcDate = new DateTime();
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
     * @return Site
     */
    public function setId(?int $id): Site
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Site|null
     */
    public function getParent(): ?Site
    {
        return $this->parent;
    }

    /**
     * @param Site|null $parent
     * @return Site
     */
    public function setParent(?Site $parent): Site
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection|Site[]|null
     */
    public function getChildren()
    {
        return $this->children->filter(function (Site $site) {
            return ! $site->isDeleted();
        });
    }

    /**
     * @param Collection|Site[]|null $children
     * @return Site
     */
    public function setChildren($children): Site
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
     * @return Site
     */
    public function setName(?string $name): Site
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        if (is_null($this->slug) && ! is_null($this->getName())) {
            $this->setSlug(self::slugify($this->getName()));
        }
        return $this->slug;
    }

    /**
     * @param string|null $slug
     * @return Site
     */
    public function setSlug(?string $slug): Site
    {
        $this->slug = $slug;
        return $this;
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
     * @return Site
     */
    public function setAlternativeRoute(?string $alternativeRoute): Site
    {
        $this->alternativeRoute = $alternativeRoute;
        return $this;
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
     * @return Site
     */
    public function setSocialMediaImage(?Media $socialMediaImage): Site
    {
        $this->socialMediaImage = $socialMediaImage;
        return $this;
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
     * @return Site
     */
    public function setDcTitle(?string $dcTitle): Site
    {
        $this->dcTitle = $dcTitle;
        return $this;
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
     * @return Site
     */
    public function setDcCreator(?string $dcCreator): Site
    {
        $this->dcCreator = $dcCreator;
        return $this;
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
     * @return Site
     */
    public function setDcDate(?DateTime $dcDate): Site
    {
        $this->dcDate = $dcDate;
        return $this;
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
     * @return Site
     */
    public function setDcKeywords(?string $dcKeywords): Site
    {
        $this->dcKeywords = $dcKeywords;
        return $this;
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
     * @return Site
     */
    public function setDcDescription(?string $dcDescription): Site
    {
        $this->dcDescription = $dcDescription;
        return $this;
    }

    /**
     * @param SiteContent|null $parent
     * @param string|null      $area
     * @return Collection|SiteContent[]|null
     */
    public function getContent(?SiteContent $parent = null, ?string $area = null)
    {
        return $this->content->filter(function (SiteContent $siteContent) use ($parent, $area) {
            return $siteContent->getParent() === $parent && $siteContent->getArea() === $area;
        });
    }

    /**
     * @param Collection|SiteContent[]|null $content
     * @return Site
     */
    public function setContent($content): Site
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return ArrayCollection|Collection|SiteContent[]
     */
    public function getContentByParentNull()
    {
        return $this->content->filter(function (SiteContent $siteContent) {
            return $siteContent->getParent() === null;
        });
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
     * @return Site
     */
    public function setPosition(?int $position): Site
    {
        $this->position = $position;
        return $this;
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
     * @return Site
     */
    public function setPublished(?bool $published): Site
    {
        $this->published = $published;
        return $this;
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
     * @return Site
     */
    public function setMenuEntry(?bool $menuEntry): Site
    {
        $this->menuEntry = $menuEntry;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     * @return Site
     */
    public function setDeleted(bool $deleted): Site
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * @return Collection|SiteHistory[]|null
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @param Collection|SiteHistory[]|null $history
     * @return Site
     */
    public function setHistory($history): Site
    {
        $this->history = $history;
        return $this;
    }

    /**
     * @return SitePublished|null
     */
    public function getPublishedSite(): ?SitePublished
    {
        return $this->publishedSite;
    }

    /**
     * @param SitePublished|null $publishedSite
     */
    public function setPublishedSite(?SitePublished $publishedSite): void
    {
        $publishedSite->setSite($this);
        $this->publishedSite = $publishedSite;
    }

    /**
     * @return $this
     */
    public function reorderRootContent(): Site
    {
        $newPosition = 1;
        foreach ($this->getContentByParentNull() as $child) {
            $child->setPosition($newPosition);
            $newPosition++;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function reorderChildren(): Site
    {
        $newPosition = 1;
        foreach ($this->getChildren() as $child) {
            $child->setPosition($newPosition);
            $newPosition++;
        }
        return $this;
    }

    /**
     * @param string|null $text
     * @return string|null
     */
    public static function slugify(?string $text): ?string
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return null;
        }

        return $text;
    }

    /**
     * @param string $routeName
     * @return int
     */
    public static function getIdByRouteName(string $routeName): ?int
    {
        $id = str_replace(self::ROUTE_PREFIX, '', $routeName);
        if (is_numeric($id)) {
            return $id;
        }
        return null;
    }

    /**
     * @return string
     */
    public function getRouteName(): string
    {
        return self::ROUTE_PREFIX . $this->getId();
    }

    /**
     * @param SiteContent|null $parent
     * @return string[]
     * @throws ReflectionException
     */
    public function getSnippetTree(?SiteContent $parent = null): array
    {
        $tree = [];
        if (is_null($parent)) {
            foreach ($this->getContentByParentNull() as $siteContent) {
                $tree[] = $this->getSnippetTree($siteContent);
            }
            return $tree;
        }
        $item = $parent->toArray(0);
        $item['snippet'] = $parent->getSnippet()->toArray(0);
        unset($item['template']);
        foreach ($parent->getChildren() as $child) {
            if (! is_null($child->getArea())) {
                if (! isset($item['children'][$child->getArea()])) {
                    $item['children'][$child->getArea()] = [];
                }
                $item['children'][$child->getArea()][] = $this->getSnippetTree($child);
            } else {
                $item['children'][] = $this->getSnippetTree($child);
            }
        }
        return $item;
    }

    /**
     * @param SiteContent|null $parent
     * @return string[]
     * @throws ReflectionException
     */
    public function getSiteContentAsArray(?SiteContent $parent = null): array
    {
        $content = [];
        if (is_null($parent)) {
            foreach ($this->getContentByParentNull() as $siteContent) {
                $content[] = $this->getSiteContentAsArray($siteContent);
            }
            return $content;
        }
        $item = $parent->toArray(0);
        $item['snippet'] = $parent->getSnippet()->toArray(0);
        $item['data'] = $parent->getDataAsKeyValueArray();
        foreach ($parent->getChildren() as $child) {
            if (! is_null($child->getArea())) {
                if (! isset($item['children'][$child->getArea()])) {
                    $item['children'][$child->getArea()] = [];
                }
                $item['children'][$child->getArea()][] = $this->getSiteContentAsArray($child);
            } else {
                $item['children'][] = $this->getSiteContentAsArray($child);
            }
        }
        return $item;
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

    // @codingStandardsIgnoreStart

    /**
     * @param string|null               $object
     * @param ExecutionContextInterface $context
     * @param                           $payload
     */
    public function validateAlternativeRoute(?string $object, ExecutionContextInterface $context, $payload)
    {
        if (! is_null($object)) {
            $value = explode(':', $object);
            if (count($value) !== 2) {
                $context->buildViolation('Der Routenname muss aus Controller:method bestehen!')
                    ->atPath('alternativeRoute')
                    ->addViolation();
            } elseif (! method_exists($value[0], $value[1])) {
                $context->buildViolation('Die Klasse und/oder Methode existiert nicht!')
                    ->atPath('alternativeRoute')
                    ->addViolation();
            }
        }
    }
    // @codingStandardsIgnoreEnd

    /**
     * @return bool
     */
    public function canBePublished(): bool
    {
        /** @var SiteHistory $lastSiteHistory */
        $lastSiteHistory = $this->getHistory()->first();
        return $lastSiteHistory->getAction() !== SiteHistory::ACTION_PUBLISHED || is_null($this->getPublishedSite());
    }

    /**
     * @param int $relationDepth
     * @return string[]
     */
    public function toArray(int $relationDepth = 0): array
    {
        $data = parent::toArray($relationDepth);
        $data['hasPublishedSite'] = (! is_null($this->getPublishedSite()) && $this->getPublishedSite()->isPublished());
        $data['historyCount'] = $this->history->count();
        $data['route'] = $this->getRouteName();
        $data['canBePublished'] = $this->canBePublished();
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

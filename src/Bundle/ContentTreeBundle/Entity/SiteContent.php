<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ReflectionException;
use Trollfjord\Core\Entity\AbstractEntity;
use function count;
use function is_null;
use function ksort;
use function preg_match_all;

/**
 * SiteContent Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\ContentTreeBundle\Repository\SiteContentRepository")
 */
class SiteContent extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id;

    /**
     * @var Site|null
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="content")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Site $site;

    /**
     * @var Snippet|null
     * @ORM\ManyToOne(targetEntity="Snippet", inversedBy="content")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected ?Snippet $snippet;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     */
    protected bool $form = true;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=false)
     */
    protected ?string $template;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $area;

    /**
     * @var SiteContent|null
     * @ORM\ManyToOne(targetEntity="SiteContent", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected ?SiteContent $parent;

    /**
     * @var SiteContent[]|Collection|null
     * @ORM\OneToMany(targetEntity="SiteContent", mappedBy="parent", cascade={"persist"}, orphanRemoval=true, indexBy="id")
     * @ORM\OrderBy({"position":"ASC"})
     */
    protected ?Collection $children;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $name;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var SiteContentData[]|Collection|null
     * @ORM\OneToMany(targetEntity="SiteContentData", mappedBy="siteContent", cascade={"persist"}, orphanRemoval=true)
     */
    protected ?Collection $data;

    /**
     * SiteContent constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->data = new ArrayCollection();
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
     * @return SiteContent
     */
    public function setId(?int $id): SiteContent
    {
        $this->id = $id;
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
     * @param Site|null $site
     * @return SiteContent
     */
    public function setSite(?Site $site): SiteContent
    {
        $this->site = $site;
        foreach ($this->getChildren() as $child) {
            $child->setSite($site);
        }
        return $this;
    }

    /**
     * @return Snippet|null
     */
    public function getSnippet(): ?Snippet
    {
        return $this->snippet;
    }

    /**
     * @param Snippet|null $snippet
     * @return SiteContent
     */
    public function setSnippet(?Snippet $snippet): SiteContent
    {
        $this->snippet = $snippet;
        return $this;
    }

    /**
     * @return bool
     */
    public function isForm(): bool
    {
        return $this->form;
    }

    /**
     * @param bool $form
     * @return SiteContent
     */
    public function setForm(bool $form): SiteContent
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string|null $template
     * @return SiteContent
     */
    public function setTemplate(?string $template): SiteContent
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getArea(): ?string
    {
        return $this->area;
    }

    /**
     * @param string|null $area
     * @return SiteContent
     */
    public function setArea(?string $area): SiteContent
    {
        $this->area = $area;
        return $this;
    }

    /**
     * @return SiteContent|null
     */
    public function getParent(): ?SiteContent
    {
        return $this->parent;
    }

    /**
     * @param SiteContent|null $parent
     * @return SiteContent
     */
    public function setParent(?SiteContent $parent): SiteContent
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection|SiteContent[]|null
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Collection|SiteContent[]|null $children
     * @return SiteContent
     */
    public function setChildren($children): SiteContent
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @param string $area
     * @return Collection|SiteContent[]
     */
    public function getChildrenByArea(string $area): Collection
    {
        return $this->children->filter(function (SiteContent $siteContent) use ($area) {
            return $siteContent->getArea() === $area;
        });
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
     * @return SiteContent
     */
    public function setName(?string $name): SiteContent
    {
        $this->name = $name;
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
     * @return SiteContent
     */
    public function setPosition(?int $position): SiteContent
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Collection|SiteContentData[]|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Collection|SiteContentData[]|null $data
     * @return SiteContent
     */
    public function setData($data): SiteContent
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param string               $key
     * @param SiteContentData|null $parent
     * @return SiteContentData|null
     */
    public function getDataByKey(string $key, ?SiteContentData $parent = null): ?SiteContentData
    {
        foreach ($this->getData() as $data) {
            if ($data->getKey() === $key && $data->getParent() === $parent) {
                return $data;
            }
        }
        return null;
    }

    /**
     * @param SiteContentData|null $parent
     * @return array|SiteContentData[]
     */
    public function getChildrenByParent(?SiteContentData $parent = null): array
    {
        $children = [];
        foreach ($this->getData() as $child) {
            if ($child->getParent() === $parent) {
                $children[$child->getKey()] = $child;
            }
        }
        ksort($children);
        return $children;
    }

    /**
     * @param string $keypath
     * @return SiteContentData|null
     */
    public function getDataByKeyPath(string $keypath): ?SiteContentData
    {
        preg_match_all('/\[(.*)\]/Uism', $keypath, $keys);
        $item = null;
        foreach ($keys[1] as $key) {
            $item = $this->getDataByKey($key, $item);
            if (is_null($item)) {
                break;
            }
        }
        return $item;
    }

    /**
     * @param string|null $area
     * @return $this
     */
    public function reorderChildrenByArea(?string $area): SiteContent
    {
        $newPosition = 1;
        foreach ($this->getChildrenByArea($area) as $child) {
            $child->setPosition($newPosition);
            $newPosition++;
        }
        return $this;
    }

    /**
     * @param SiteContentData $siteContentData
     */
    public function reorderDataChildren(SiteContentData $siteContentData): void
    {
        $newKey = 0;
        foreach ($siteContentData->getChildren() as $child) {
            $child->setKey($newKey);
            $newKey++;
        }
    }

    /**
     * @param SiteContentData|null $parent
     * @return string[]
     */
    public function getDataAsKeyValueArray(?SiteContentData $parent = null): array
    {
        $result = [];
        /** @var SiteContentData[] $items */
        $items = $this->getData()->filter(function (SiteContentData $siteContentData) use ($parent) {
            return $siteContentData->getParent() === $parent;
        })->toArray();

        foreach ($items as $item) {
            $children = $this->getDataAsKeyValueArray($item);
            if (count($children) > 0) {
                $result[$item->getKey()] = $children;
            } else {
                $result[$item->getKey()] = $item->getValue();
            }
        }
        ksort($result);
        return $result;
    }

    /**
     * @param int $relationDepth
     * @return string[]
     * @throws ReflectionException
     */
    public function toArray(int $relationDepth = 0): array
    {
        $data = parent::toArray($relationDepth);
        if ($this->getParent()) {
            if ($this->area) {
                $data['totalItems'] = $this->getParent()->getChildrenByArea($this->area)->count();
            } else {
                $data['totalItems'] = $this->getParent()->getChildren()->count();
            }
        } else {
            $data['totalItems'] = $this->getChildren()->count();
        }
        return $data;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);

            $children = new ArrayCollection();
            foreach ($this->getChildren() as $child) {
                $childClone = clone $child;
                $childClone->setParent($this);
                $children->add($childClone);
            }
            $this->children = $children;

            $dataCollection = new ArrayCollection();
            foreach ($this->getChildrenByParent(null) as $data) {
                $dataClone = clone $data;
                $dataClone->setSiteContent($this);
                $dataCollection->add($dataClone);
            }
            $this->data = $dataCollection;
        }
    }
}

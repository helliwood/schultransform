<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Trollfjord\Core\Entity\AbstractEntity;
use function iterator_to_array;

/**
 * SiteContentData Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\ContentTreeBundle\Repository\SiteContentDataRepository")
 * @ORM\Table(uniqueConstraints={
 *   @ORM\UniqueConstraint(columns={"site_content_id", "parent_id", "key"})
 * })
 */
class SiteContentData extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id;

    /**
     * @var SiteContentData|null
     * @ORM\ManyToOne(targetEntity="SiteContentData", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected ?SiteContentData $parent = null;

    /**
     * @var string|null
     * @ORM\Column(name="`key`", type="string", length=150, nullable=false)
     */
    protected ?string $key;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $value = null;

    /**
     * @var SiteContentData[]|Collection|null
     * @ORM\OneToMany(targetEntity="SiteContentData", cascade={"persist"}, mappedBy="parent", indexBy="key", orphanRemoval=true)
     * @ORM\OrderBy({"key":"ASC"})
     */
    protected ?Collection $children;

    /**
     * @var SiteContent|null
     * @ORM\ManyToOne(targetEntity="SiteContent", inversedBy="data")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?SiteContent $siteContent;

    /**
     * SiteContentData constructor.
     */
    public function __construct(?string $key = null, ?string $value = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->children = new ArrayCollection();
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
     * @return SiteContentData
     */
    public function setId(?int $id): SiteContentData
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return SiteContentData|null
     */
    public function getParent(): ?SiteContentData
    {
        return $this->parent;
    }

    /**
     * @param SiteContentData|null $parent
     * @return SiteContentData
     */
    public function setParent(?SiteContentData $parent): SiteContentData
    {
        if ($parent) {
            $parent->getChildren()->add($this);
        }
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     * @return SiteContentData
     */
    public function setKey(?string $key): SiteContentData
    {
        $this->key = $key;
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
     * @return SiteContentData
     */
    public function setValue(?string $value): SiteContentData
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return Collection|SiteContentData[]|null
     * @throws Exception
     */
    public function getChildren()
    {
        // Die Daten müssen noch mal sortiert werden, weil bei SQL ansonsten die 10 vor der 2 kommt
        $iterator = $this->children->getIterator();
        $iterator->uasort(fn(SiteContentData $a, SiteContentData $b) => $a->getKey() <=> $b->getKey());

        return new ArrayCollection(iterator_to_array($iterator));
    }

    /**
     * @param Collection|SiteContentData[]|null $children
     * @return SiteContentData
     */
    public function setChildren($children): SiteContentData
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @return SiteContent|null
     */
    public function getSiteContent(): ?SiteContent
    {
        return $this->siteContent;
    }

    /**
     * @param SiteContent|null $siteContent
     * @return SiteContentData
     */
    public function setSiteContent(?SiteContent $siteContent): SiteContentData
    {
        $this->siteContent = $siteContent;
        foreach ($this->getChildren() as $child) {
            $child->setSiteContent($siteContent);
        }
        $siteContent->getData()->add($this);
        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);

            $childrenCollection = new ArrayCollection();
            foreach ($this->getChildren() as $child) {
                $childClone = clone $child;
                $childClone->setSiteContent($this->getSiteContent());
                $childClone->setParent($this);
                $childrenCollection->add($childClone);
            }
            $this->children = $childrenCollection;
        }
    }
}
